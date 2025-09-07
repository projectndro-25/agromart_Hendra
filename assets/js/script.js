// Notifikasi auto-close setelah 3 detik
document.addEventListener("DOMContentLoaded", () => {
    const alerts = document.querySelectorAll(".alert");
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = "0";
            alert.style.transform = "translateY(-10px)";
            setTimeout(() => alert.remove(), 500);
        }, 3000);
    });
});

// Sidebar toggle (opsional kalau mau responsif)
const toggleSidebar = document.querySelector("#toggleSidebar");
if (toggleSidebar) {
    toggleSidebar.addEventListener("click", () => {
        document.querySelector(".sidebar").classList.toggle("active");
        document.querySelector(".content").classList.toggle("full");
    });
}
