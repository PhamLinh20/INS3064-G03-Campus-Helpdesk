<?php
/**
 * Master Footer for Technicians Portal
 */
?>
    </div>
  </main>
</div>

<!-- Interactive Scripts (Avatar dropdown, queue search, etc.) -->
<script>
  document.addEventListener('DOMContentLoaded', function () {
    // 1. Avatar Dropdown Toggle with Caret Animation
    const techUserBtn = document.getElementById('tech-user-menu-btn');
    const techDropdown = document.getElementById('tech-user-dropdown');
    const techChevron = document.getElementById('tech-user-chevron');

    if (techUserBtn && techDropdown) {
      techUserBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        const isClosed = techDropdown.classList.contains('hidden');
        techDropdown.classList.toggle('hidden');
        if (techChevron) {
          techChevron.style.transform = isClosed ? 'rotate(180deg)' : 'rotate(0deg)';
        }
      });

      document.addEventListener('click', function (e) {
        if (!techDropdown.contains(e.target) && !techUserBtn.contains(e.target)) {
          techDropdown.classList.add('hidden');
          if (techChevron) {
            techChevron.style.transform = 'rotate(0deg)';
          }
        }
      });
    }
  });
</script>

</body>
</html>
