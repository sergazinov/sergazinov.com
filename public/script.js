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

function getSiteLanguage() {
  return localStorage.getItem("siteLanguage") === "en" ? "en" : "ru";
}

function getScheduleTimezoneLabel(timezone, language = getSiteLanguage()) {
  if (timezone === "moscow") {
    return language === "ru" ? "МОСКВА · UTC+3" : "MOSCOW TIME · UTC+3";
  }

  return language === "ru" ? "АСТАНА · UTC+5" : "ASTANA TIME · UTC+5";
}

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

    timezoneToggle.textContent = getScheduleTimezoneLabel(timezone);

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

let scheduleStatusUpdater = null;

const scheduleDays = Array.from(document.querySelectorAll(".schedule-day"));

if (scheduleDays.length > 0) {
  const scheduleSessions = [];

  scheduleDays.forEach((dayElement, dayIndex) => {
    const weekday = dayIndex + 1;

    dayElement.querySelectorAll(".class-session").forEach((sessionElement) => {
      const timeElement = sessionElement.querySelector(".class-time");

      if (!timeElement) {
        return;
      }

      const astanaTime =
        timeElement.dataset.astanaTime || timeElement.textContent.trim();

      const match = astanaTime.match(
        /(\d{1,2}:\d{2})\s*[–-]\s*(\d{1,2}:\d{2})/,
      );

      if (!match) {
        return;
      }

      const [, startTime, endTime] = match;

      function timeToMinutes(time) {
        const [hours, minutes] = time.split(":").map(Number);

        return hours * 60 + minutes;
      }

      scheduleSessions.push({
        element: sessionElement,
        weekday,
        start: timeToMinutes(startTime),
        end: timeToMinutes(endTime),
      });
    });
  });

  function getAstanaNow(date = new Date()) {
    const parts = new Intl.DateTimeFormat("en-GB", {
      timeZone: "Asia/Almaty",
      weekday: "short",
      hour: "2-digit",
      minute: "2-digit",
      second: "2-digit",
      hour12: false,
    }).formatToParts(date);

    const values = {};

    parts.forEach((part) => {
      if (part.type !== "literal") {
        values[part.type] = part.value;
      }
    });

    const weekdays = {
      Mon: 1,
      Tue: 2,
      Wed: 3,
      Thu: 4,
      Fri: 5,
      Sat: 6,
      Sun: 7,
    };

    return {
      weekday: weekdays[values.weekday],
      minutes:
        Number(values.hour) * 60 +
        Number(values.minute) +
        Number(values.second) / 60,
    };
  }

  function formatScheduleDuration(minutes) {
    const language = getSiteLanguage();
    const totalMinutes = Math.max(1, Math.ceil(minutes));

    const days = Math.floor(totalMinutes / 1440);
    const hours = Math.floor((totalMinutes % 1440) / 60);
    const remainingMinutes = totalMinutes % 60;

    const units =
      language === "ru"
        ? { day: "Д", hour: "Ч", minute: "М" }
        : { day: "D", hour: "H", minute: "M" };

    const parts = [];

    if (days > 0) {
      parts.push(`${days}${units.day}`);
    }

    if (hours > 0) {
      parts.push(`${hours}${units.hour}`);
    }

    if (remainingMinutes > 0 || parts.length === 0) {
      parts.push(`${remainingMinutes}${units.minute}`);
    }

    return parts.join(" · ");
  }

  function addScheduleStatus(session, type, minutes) {
    const language = getSiteLanguage();

    const labels =
      language === "ru"
        ? {
            now: "СЕЙЧАС",
            next: "ДАЛЬШЕ",
            endsIn: "ДО КОНЦА",
            startsIn: "НАЧАЛО ЧЕРЕЗ",
          }
        : {
            now: "NOW",
            next: "NEXT",
            endsIn: "ENDS IN",
            startsIn: "STARTS IN",
          };

    const statusElement = document.createElement("div");

    statusElement.className = `class-status is-${type}`;

    if (type === "now") {
      statusElement.textContent =
        `${labels.now} · ${labels.endsIn} ` + formatScheduleDuration(minutes);
    } else {
      statusElement.textContent =
        `${labels.next} · ${labels.startsIn} ` +
        formatScheduleDuration(minutes);
    }

    session.element.querySelector(".class-info")?.appendChild(statusElement);

    session.element.classList.add(`is-${type}`);
  }

  function updateScheduleStatus(date = new Date()) {
    const now = getAstanaNow(date);

    let currentSession = null;
    let nextSession = null;
    let shortestWait = Infinity;

    scheduleSessions.forEach((session) => {
      session.element.classList.remove("is-now", "is-next");
      session.element.querySelector(".class-status")?.remove();

      if (
        session.weekday === now.weekday &&
        now.minutes >= session.start &&
        now.minutes < session.end
      ) {
        currentSession = session;
      }

      let wait =
        ((session.weekday - now.weekday + 7) % 7) * 1440 +
        session.start -
        now.minutes;

      if (wait <= 0) {
        wait += 7 * 1440;
      }

      if (wait < shortestWait) {
        shortestWait = wait;
        nextSession = session;
      }
    });

    if (currentSession) {
      addScheduleStatus(
        currentSession,
        "now",
        currentSession.end - now.minutes,
      );
    }

    if (nextSession) {
      addScheduleStatus(nextSession, "next", shortestWait);
    }
  }

  scheduleStatusUpdater = updateScheduleStatus;

  updateScheduleStatus();

  setInterval(updateScheduleStatus, 30000);
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

    const language = getSiteLanguage();

    const dateLocale = language === "ru" ? "ru-RU" : "en-US";

    const dateFormatter = new Intl.DateTimeFormat(dateLocale, {
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

    const localLabel = language === "ru" ? "МЕСТНОЕ" : "LOCAL";

    currentTimezoneElement.textContent = `${localLabel} · ${formatUtcOffset(now)}`;
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

    const language = getSiteLanguage();
    const locale = language === "ru" ? "ru-RU" : "en-GB";

    const parts = new Intl.DateTimeFormat(locale, {
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

  function formatDuration(milliseconds) {
    const language = getSiteLanguage();

    const totalSeconds = Math.floor(Math.abs(milliseconds) / 1000);

    const days = Math.floor(totalSeconds / 86400);
    const hours = Math.floor((totalSeconds % 86400) / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = totalSeconds % 60;

    const units =
      language === "ru"
        ? {
            day: "Д",
            hour: "Ч",
            minute: "М",
            second: "С",
          }
        : {
            day: "D",
            hour: "H",
            minute: "M",
            second: "S",
          };

    const parts = [];

    if (days > 0) {
      parts.push(`${days}${units.day}`);
    }

    parts.push(
      `${String(hours).padStart(2, "0")}${units.hour}`,
      `${String(minutes).padStart(2, "0")}${units.minute}`,
      `${String(seconds).padStart(2, "0")}${units.second}`,
    );

    return parts.join(" · ");
  }

  function updateDeadlineDisplay() {
    const timezone = getDeadlineTimezone();
    const now = Date.now();

    const hour = 60 * 60 * 1000;
    const day = 24 * hour;

    deadlineElements.forEach((deadlineElement) => {
      const deadlineDate = new Date(deadlineElement.dataset.dueAt);
      const remaining = deadlineDate.getTime() - now;

      const timeElement = deadlineElement.querySelector(".deadline-time");

      const countdownElement = deadlineElement.querySelector(
        ".deadline-countdown-value",
      );

      const countdownLabel = deadlineElement.querySelector(
        ".deadline-countdown-label",
      );

      timeElement.textContent = formatDeadlineDate(deadlineDate, timezone);

      countdownElement.classList.remove(
        "is-safe",
        "is-warning",
        "is-urgent",
        "is-overdue",
      );

      const language = getSiteLanguage();

      if (remaining <= 0) {
        countdownLabel.textContent =
          language === "ru" ? "Просрочено на" : "Overdue by";

        countdownElement.textContent = formatDuration(remaining);
        countdownElement.classList.add("is-overdue");

        return;
      }

      countdownLabel.textContent = language === "ru" ? "Осталось" : "Due in";

      countdownElement.textContent = formatDuration(remaining);

      if (remaining > 2 * day) {
        countdownElement.classList.add("is-safe");
      } else if (remaining > day) {
        countdownElement.classList.add("is-warning");
      } else {
        countdownElement.classList.add("is-urgent");
      }
    });
  }

  updateDeadlineDisplay();

  setInterval(updateDeadlineDisplay, 1000);

  if (deadlineTimezoneToggle) {
    deadlineTimezoneToggle.addEventListener("click", updateDeadlineDisplay);
  }
}

const translations = {
  en: {
    "header.homeLabel": "Sergazinov home",
    "header.languageLabel": "Language",
    "header.themeLabel": "Toggle theme",

    "home.eyebrow": "CDS · HSE Moscow",
    "home.titleFirst": "Computing",
    "home.titleSecond": "Data Science",
    "home.description":
      "A personal learning hub documenting my journey through Full-Stack Development and the Computing & Data Science program at HSE Moscow.",
    "home.navigationLabel": "Main navigation",

    "nav.courses": "Courses",
    "nav.schedule": "Schedule",
    "nav.deadlines": "Deadlines",
    "nav.admin": "Admin",
    "nav.home": "Home",
    "schedule.pageTitle": "Schedule · Sergazinov",
    "schedule.eyebrow": "COMPUTING AND DATA SCIENCE",
    "schedule.title": "Schedule",
    "schedule.description":
      "Class schedule for HSE Computing and Data Science.",
    "schedule.localTime": "LOCAL TIME",
    "schedule.timezoneLabel": "Switch schedule timezone",
    "schedule.groupLabel": "Select group",
    "schedule.freeDay": "Free day",

    "day.monday": "Monday",
    "day.tuesday": "Tuesday",
    "day.wednesday": "Wednesday",
    "day.thursday": "Thursday",
    "day.friday": "Friday",
    "day.saturday": "Saturday",
    "day.sunday": "Sunday",

    "class.lecture": "Lecture",
    "class.seminar": "Seminar",

    "course.linearAlgebra": "Linear Algebra",
    "course.discreteMath": "Discrete Mathematics",
    "course.russianHistory": "Russian History",
    "course.statehood": "Russian Statehood",
    "course.english": "English",

    "deadlines.openAssignment": "Open assignment",
    "deadlines.pageTitle": "Deadlines · Sergazinov",
    "deadlines.eyebrow": "COMPUTING AND DATA SCIENCE",
    "deadlines.title": "Deadlines",
    "deadlines.description": "Upcoming coursework and submission deadlines.",
    "deadlines.timezoneLabel": "Switch deadline timezone",
    "deadlines.emptyTitle": "No deadlines yet.",
    "deadlines.emptyText": "Upcoming deadlines will appear here.",
    "deadlines.deadlineLabel": "Deadline",
    "deadlines.dueIn": "Due in",
  },

  ru: {
    "header.homeLabel": "Главная страница Sergazinov",
    "header.languageLabel": "Язык",
    "header.themeLabel": "Переключить тему",

    "home.eyebrow": "КНАД · НИУ ВШЭ, Москва",
    "home.titleFirst": "Компьютерные науки",
    "home.titleSecond": "Анализ данных",
    "home.description":
      "Мой учебный проект о Full-Stack разработке и обучении на программе «Компьютерные науки и анализ данных» в НИУ ВШЭ.",
    "home.navigationLabel": "Основная навигация",

    "nav.courses": "Курсы",
    "nav.schedule": "Расписание",
    "nav.deadlines": "Дедлайны",
    "nav.admin": "Админ",
    "nav.home": "Главная",
    "schedule.pageTitle": "Расписание · Sergazinov",
    "schedule.eyebrow": "КНАД · НИУ ВШЭ, МОСКВА",
    "schedule.title": "Расписание",
    "schedule.description":
      "Расписание занятий программы «Компьютерные науки и анализ данных» НИУ ВШЭ.",
    "schedule.localTime": "МЕСТНОЕ ВРЕМЯ",
    "schedule.timezoneLabel": "Переключить часовой пояс расписания",
    "schedule.groupLabel": "Выбрать группу",
    "schedule.freeDay": "Свободный день",

    "day.monday": "Понедельник",
    "day.tuesday": "Вторник",
    "day.wednesday": "Среда",
    "day.thursday": "Четверг",
    "day.friday": "Пятница",
    "day.saturday": "Суббота",
    "day.sunday": "Воскресенье",

    "class.lecture": "Лекция",
    "class.seminar": "Семинар",

    "course.linearAlgebra": "Линейная алгебра",
    "course.discreteMath": "Дискретная математика",
    "course.russianHistory": "История России",
    "course.statehood": "ОРГ",
    "course.english": "Английский язык",

    "deadlines.openAssignment": "Открыть задание",
    "deadlines.pageTitle": "Дедлайны · Sergazinov",
    "deadlines.eyebrow": "КНАД · НИУ ВШЭ, МОСКВА",
    "deadlines.title": "Дедлайны",
    "deadlines.description": "Предстоящие учебные задания и сроки сдачи.",
    "deadlines.timezoneLabel": "Переключить часовой пояс дедлайнов",
    "deadlines.emptyTitle": "Дедлайнов пока нет.",
    "deadlines.emptyText": "Предстоящие дедлайны появятся здесь.",
    "deadlines.deadlineLabel": "Дедлайн",
    "deadlines.dueIn": "Осталось",
  },
};

const languageButtons = document.querySelectorAll("[data-language]");

function getCurrentLanguage() {
  const savedLanguage = localStorage.getItem("siteLanguage");

  return savedLanguage === "en" ? "en" : "ru";
}

const courseTranslationKeys = {
  "linear-algebra": "course.linearAlgebra",
  "discrete-mathematics": "course.discreteMath",
  "russian-history": "course.russianHistory",
  "russian-statehood": "course.statehood",
  english: "course.english",
};

function applyLanguage(language) {
  const dictionary = translations[language];

  document.documentElement.lang = language;

  document.querySelectorAll("[data-i18n]").forEach((element) => {
    const key = element.dataset.i18n;

    if (dictionary[key]) {
      element.textContent = dictionary[key];
    }
  });

  document.querySelectorAll("[data-homework-title]").forEach((element) => {
    const originalTitle = element.dataset.homeworkTitle;
    const match = originalTitle.match(/^Homework\s+(\d+)$/i);

    if (match) {
      element.textContent =
        language === "ru" ? `ДЗ ${match[1]}` : `Homework ${match[1]}`;
    } else {
      element.textContent = originalTitle;
    }
  });

  document.querySelectorAll("[data-i18n-course]").forEach((element) => {
    const slug = element.dataset.i18nCourse;
    const key = courseTranslationKeys[slug];

    if (key && dictionary[key]) {
      element.textContent = dictionary[key];
    }
  });

  document.querySelectorAll("[data-i18n-aria-label]").forEach((element) => {
    const key = element.dataset.i18nAriaLabel;

    if (dictionary[key]) {
      element.setAttribute("aria-label", dictionary[key]);
    }
  });

  languageButtons.forEach((button) => {
    const isActive = button.dataset.language === language;

    button.classList.toggle("is-active", isActive);
    button.setAttribute("aria-pressed", String(isActive));
  });

  localStorage.setItem("siteLanguage", language);
  if (timezoneToggle) {
    const timezone =
      localStorage.getItem("scheduleTimezone") === "moscow"
        ? "moscow"
        : "astana";

    timezoneToggle.textContent = getScheduleTimezoneLabel(timezone, language);
  }

  if (scheduleStatusUpdater) {
    scheduleStatusUpdater();
  }
}

languageButtons.forEach((button) => {
  button.addEventListener("click", () => {
    applyLanguage(button.dataset.language);
  });
});

applyLanguage(getCurrentLanguage());

/* ---------- HOME TYPING DEMO ---------- */

(() => {
  const typingText = document.getElementById("typingText");
  const typingCursor = document.querySelector(".typing-cursor");

  if (!typingText || !typingCursor) {
    return;
  }

  const phrases = {
    ru: [
      {
        text: "тут прячут твою домашку...",
        hold: 2100,
      },
      {
        text: "тут хранят твои дедлайны...",
        hold: 1300,
      },
      {
        text: "тут спрятано твоё расписание...",
        hold: 3200,
      },
    ],

    en: [
      {
        text: "your homework is hidden here...",
        hold: 2100,
      },
      {
        text: "your deadlines live here...",
        hold: 1300,
      },
      {
        text: "your schedule is hidden here...",
        hold: 3200,
      },
    ],
  };

  const reducedMotionQuery = window.matchMedia(
    "(prefers-reduced-motion: reduce)",
  );

  let animationVersion = 0;

  function wait(milliseconds) {
    return new Promise((resolve) => {
      window.setTimeout(resolve, milliseconds);
    });
  }

  function getTypingPhrases() {
    return phrases[getSiteLanguage()] || phrases.ru;
  }

  async function typePhrase(value, version) {
    for (const character of value) {
      if (version !== animationVersion) {
        return false;
      }

      typingText.textContent += character;

      const delay = 45 + Math.random() * 45;

      await wait(delay);
    }

    return version === animationVersion;
  }

  async function erasePhrase(version) {
    while (typingText.textContent.length > 0) {
      if (version !== animationVersion) {
        return false;
      }

      typingText.textContent = typingText.textContent.slice(0, -1);

      const delay = 18 + Math.random() * 18;

      await wait(delay);
    }

    return version === animationVersion;
  }

  async function runTypingLoop(version) {
    await wait(450);

    while (version === animationVersion) {
      const currentPhrases = getTypingPhrases();

      for (const phrase of currentPhrases) {
        const typed = await typePhrase(phrase.text, version);

        if (!typed) {
          return;
        }

        await wait(phrase.hold);

        if (version !== animationVersion) {
          return;
        }

        const erased = await erasePhrase(version);

        if (!erased) {
          return;
        }

        await wait(300);
      }

      await wait(500);
    }
  }

  function restartTypingDemo() {
    animationVersion += 1;

    const version = animationVersion;

    typingText.textContent = "";

    if (reducedMotionQuery.matches) {
      typingText.textContent = getTypingPhrases()[0].text;
      typingCursor.hidden = true;

      return;
    }

    typingCursor.hidden = false;

    runTypingLoop(version);
  }

  document.querySelectorAll(".language-option").forEach((button) => {
    button.addEventListener("click", () => {
      window.setTimeout(restartTypingDemo, 0);
    });
  });

  if (typeof reducedMotionQuery.addEventListener === "function") {
    reducedMotionQuery.addEventListener("change", restartTypingDemo);
  }

  restartTypingDemo();
})();
