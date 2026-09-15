const themeToggle = document.getElementById("themeToggle");
const savedTheme = localStorage.getItem("theme");

if (savedTheme === "light") {
  document.body.classList.add("light");
  themeToggle.setAttribute("aria-pressed", "true");
} else {
  themeToggle.setAttribute("aria-pressed", "false");
}

themeToggle.addEventListener("click", () => {
  document.body.classList.toggle("light");

  const isLight = document.body.classList.contains("light");

  localStorage.setItem("theme", isLight ? "light" : "dark");
  themeToggle.setAttribute("aria-pressed", isLight ? "true" : "false");
});

const timezoneToggle = document.getElementById("timezoneToggle");

if (timezoneToggle) {
  const classTimes = document.querySelectorAll(".class-time");

  classTimes.forEach((timeElement) => {
    timeElement.dataset.astanaTime = timeElement.textContent.trim();
  });

  function shiftTime(time, minutesToAdd) {
    const [hours, minutes] = time.split(":").map(Number);

    let totalMinutes = hours * 60 + minutes + minutesToAdd;

    totalMinutes = ((totalMinutes % 1440) + 1440) % 1440;

    const newHours = Math.floor(totalMinutes / 60);
    const newMinutes = totalMinutes % 60;

    return `${String(newHours).padStart(2, "0")}:${String(newMinutes).padStart(2, "0")}`;
  }

  function convertTimeRange(timeRange, timezone) {
    const match = timeRange.match(/(\d{1,2}:\d{2})\s*[–-]\s*(\d{1,2}:\d{2})/);

    if (!match) {
      return timeRange;
    }

    const [, startTime, endTime] = match;

    if (timezone === "moscow") {
      return `${shiftTime(startTime, -120)}–${shiftTime(endTime, -120)}`;
    }

    return `${startTime}–${endTime}`;
  }

  function applyTimezone(timezone) {
    classTimes.forEach((timeElement) => {
      const astanaTime = timeElement.dataset.astanaTime;

      timeElement.textContent = convertTimeRange(astanaTime, timezone);
    });

    if (timezone === "moscow") {
      timezoneToggle.textContent = "MOSCOW TIME · UTC+3";
    } else {
      timezoneToggle.textContent = "ASTANA TIME · UTC+5";
    }

    localStorage.setItem("scheduleTimezone", timezone);
  }

  const savedTimezone = localStorage.getItem("scheduleTimezone") || "astana";

  applyTimezone(savedTimezone);

  timezoneToggle.addEventListener("click", () => {
    const currentTimezone =
      localStorage.getItem("scheduleTimezone") || "astana";

    const newTimezone = currentTimezone === "astana" ? "moscow" : "astana";

    applyTimezone(newTimezone);
  });
}

const currentDateElement = document.getElementById("currentDate");
const currentTimeElement = document.getElementById("currentTime");
const currentTimezoneElement = document.getElementById("currentTimezone");

if (currentDateElement && currentTimeElement && currentTimezoneElement) {
  function formatUtcOffset(date) {
    const offsetMinutes = -date.getTimezoneOffset();

    const sign = offsetMinutes >= 0 ? "+" : "-";

    const absoluteMinutes = Math.abs(offsetMinutes);

    const hours = Math.floor(absoluteMinutes / 60);
    const minutes = absoluteMinutes % 60;

    if (minutes === 0) {
      return `UTC${sign}${hours}`;
    }

    return `UTC${sign}${hours}:${String(minutes).padStart(2, "0")}`;
  }

  function updateCurrentClock() {
    const now = new Date();

    const dateFormatter = new Intl.DateTimeFormat("en-US", {
      weekday: "short",
      month: "short",
      day: "numeric",
      year: "numeric",
    });

    const timeFormatter = new Intl.DateTimeFormat("en-GB", {
      hour: "2-digit",
      minute: "2-digit",
      second: "2-digit",
      hour12: false,
    });

    currentDateElement.textContent = dateFormatter.format(now).toUpperCase();

    currentTimeElement.textContent = timeFormatter.format(now);

    currentTimezoneElement.textContent = `LOCAL · ${formatUtcOffset(now)}`;
  }

  function runClock() {
    updateCurrentClock();

    const delay = 1000 - new Date().getMilliseconds();

    setTimeout(runClock, delay);
  }

  runClock();
}

const deadlineElements = document.querySelectorAll("[data-deadline]");

if (deadlineElements.length > 0) {
  const deadlineTimezoneToggle = document.getElementById("timezoneToggle");

  const deadlineTimezones = {
    astana: {
      zone: "Asia/Almaty",
      suffix: "UTC+5",
    },

    moscow: {
      zone: "Europe/Moscow",
      suffix: "UTC+3",
    },
  };

  function getDeadlineTimezone() {
    return localStorage.getItem("scheduleTimezone") === "moscow"
      ? "moscow"
      : "astana";
  }

  function formatDeadlineDate(date, timezone) {
    const settings = deadlineTimezones[timezone];

    const parts = new Intl.DateTimeFormat("en-GB", {
      timeZone: settings.zone,
      day: "2-digit",
      month: "short",
      year: "numeric",
      hour: "2-digit",
      minute: "2-digit",
      hour12: false,
    }).formatToParts(date);

    const values = {};

    parts.forEach((part) => {
      if (part.type !== "literal") {
        values[part.type] = part.value;
      }
    });

    return (
      `${values.day} ${values.month.toUpperCase()} ${values.year}` +
      ` · ${values.hour}:${values.minute}` +
      ` · ${settings.suffix}`
    );
  }

  function formatCountdown(milliseconds) {
    if (milliseconds <= 0) {
      return "DEADLINE PASSED";
    }

    const totalSeconds = Math.floor(milliseconds / 1000);

    const days = Math.floor(totalSeconds / 86400);
    const hours = Math.floor((totalSeconds % 86400) / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = totalSeconds % 60;

    const parts = [];

    if (days > 0) {
      parts.push(`${days}D`);
    }

    parts.push(
      `${String(hours).padStart(2, "0")}H`,
      `${String(minutes).padStart(2, "0")}M`,
      `${String(seconds).padStart(2, "0")}S`,
    );

    return parts.join(" · ");
  }

  function updateDeadlineDisplay() {
    const timezone = getDeadlineTimezone();
    const now = Date.now();

    deadlineElements.forEach((deadlineElement) => {
      const deadlineDate = new Date(deadlineElement.dataset.dueAt);

      const timeElement = deadlineElement.querySelector(".deadline-time");

      const countdownElement = deadlineElement.querySelector(
        ".deadline-countdown-value",
      );

      timeElement.textContent = formatDeadlineDate(deadlineDate, timezone);

      countdownElement.textContent = formatCountdown(
        deadlineDate.getTime() - now,
      );
    });
  }

  updateDeadlineDisplay();

  setInterval(updateDeadlineDisplay, 1000);

  if (deadlineTimezoneToggle) {
    deadlineTimezoneToggle.addEventListener("click", updateDeadlineDisplay);
  }
}
