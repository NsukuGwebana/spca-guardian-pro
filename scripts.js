// Sidebar open/close
const sidebar = document.getElementById("sidebar");
function openSidebar() { sidebar.classList.add("sidebar-responsive"); }
function closeSidebar() { sidebar.classList.remove("sidebar-responsive"); }

// Sidebar dropdowns
const dropdownToggles = document.querySelectorAll(".dropdown-toggle");
dropdownToggles.forEach(toggle => {
  toggle.addEventListener("click", () => {
    toggle.classList.toggle("active");
    const menu = toggle.querySelector(".dropdown-menu");
    if(menu) menu.style.display = menu.style.display === "block" ? "none" : "block";
  });
});

// Notification dropdown
document.getElementById("notification-bell").addEventListener("click", () => {
  document.getElementById("notification-list").classList.toggle("show");
});

// Fetch notifications every 5 seconds
function fetchNotifications() {
  fetch("get_notifications.php")
    .then(res => res.json())
    .then(data => {
      const listEl = document.querySelector("#notification-list ul");
      const countEl = document.getElementById("notification-count");

      // Update count
      if(countEl) {
        if(data.count > 0){
          countEl.style.display = "inline";
          countEl.textContent = data.count;
        } else countEl.style.display = "none";
      }

      // Update list
      listEl.innerHTML = "";
      data.notifications.forEach(item => {
        const li = document.createElement("li");
        li.textContent = item;
        listEl.appendChild(li);
      });
    })
    .catch(console.error);
}

setInterval(fetchNotifications, 5000);
fetchNotifications();
