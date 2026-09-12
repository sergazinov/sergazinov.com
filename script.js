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