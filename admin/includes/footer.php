    </div> <!-- end of .content -->
  </div> <!-- end of .main -->

  <footer class="footer">
      &copy; <?= date('Y'); ?> AgroMart Admin Panel. All rights reserved.
  </footer>

  <!-- Script Toggle Sidebar untuk HP/Tablet -->
  <script>
    document.addEventListener("DOMContentLoaded", function () {
      const toggle = document.querySelector(".menu-toggle");
      const sidebar = document.querySelector(".sidebar");

      if (toggle && sidebar) {
        toggle.addEventListener("click", () => {
          sidebar.classList.toggle("active");
        });
      }
    });
  </script>
</body>
</html>
