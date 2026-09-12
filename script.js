const themeToggle = document.getElementById("theme-toggle");

const savedTheme = localStorage.getItem("theme");

if (savedTheme === "light") {
    document.body.classList.add("light");
    themeToggle.textContent = "☾";
} else {
    themeToggle.textContent = "☀";
}

themeToggle.addEventListener("click", function () {

    document.body.classList.toggle("light");

    if (document.body.classList.contains("light")) {

        themeToggle.textContent = "☾";

        localStorage.setItem("theme", "light");

    } else {

        themeToggle.textContent = "☀";

        localStorage.setItem("theme", "dark");
    }

});
