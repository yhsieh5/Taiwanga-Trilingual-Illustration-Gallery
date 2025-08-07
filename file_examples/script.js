document.addEventListener("DOMContentLoaded", () => {
  // ⯆ icon collapse and expand
  document.querySelectorAll('.toggle-icon').forEach(icon => {
    icon.addEventListener('click', (e) => {
      const parent = icon.closest('.tag-item') || icon.closest('.year-item');
      const next = parent.nextElementSibling;
      const children = (next && next.classList.contains('tag-children')) ? next :
        parent.querySelector('.month-children');
      if (children) {
        const show = children.style.display !== 'none';
        children.style.display = show ? 'none' : 'block';
        icon.classList.toggle('collapsed', show);
      }
      e.stopPropagation();
    });
  });

  // tag checkbox logic with highlight
  document.querySelectorAll('.tag-item input[type="checkbox"]').forEach(checkbox => {
    checkbox.addEventListener('change', function () {
      const isChecked = this.checked;
      const tagItem = this.closest('.tag-item');
      tagItem.classList.toggle('selected-tag', isChecked);

      // downward (checking all children)
      const childContainer = tagItem.nextElementSibling;
      if (childContainer && childContainer.classList.contains('tag-children')) {
        childContainer.querySelectorAll('input[type="checkbox"]').forEach(child => {
          child.checked = isChecked;
          child.closest('.tag-item').classList.toggle('selected-tag', isChecked);
        });
      }

      // upward (check or uncheck parent)
      updateParentChecked(this);
    });
  });

  function updateParentChecked(checkbox) {
    const tagChildren = checkbox.closest('.tag-children');
    if (!tagChildren) return;

    const parentItem = tagChildren.previousElementSibling;
    if (!parentItem || !parentItem.classList.contains('tag-item')) return;

    const parentCheckbox = parentItem.querySelector('input[type="checkbox"]');
    const siblingCheckboxes = tagChildren.querySelectorAll('input[type="checkbox"]');
    const anyChecked = Array.from(siblingCheckboxes).some(cb => cb.checked);

    parentCheckbox.checked = anyChecked;
    parentItem.classList.toggle('selected-tag', anyChecked);

    updateParentChecked(parentCheckbox);
  }

  // month logic (with highlight)
  document.querySelectorAll('.year-checkbox').forEach(cb => {
    cb.addEventListener('change', () => {
      const isChecked = cb.checked;
      const yearItem = cb.closest('.year-item');
      yearItem.classList.toggle('selected-tag', isChecked);

      yearItem.querySelectorAll('.month-checkbox').forEach(monthCb => {
        monthCb.checked = isChecked;
        monthCb.closest('label').classList.toggle('selected-tag', isChecked);
      });
    });
  });

  document.querySelectorAll('.month-checkbox').forEach(cb => {
    cb.addEventListener('change', () => {
      const isChecked = cb.checked;
      cb.closest('label').classList.toggle('selected-tag', isChecked);

      // separate highlighting of each month
      const label = cb.closest('label');
      if (label) label.classList.toggle('selected-tag', isChecked);

      const year = cb.dataset.year;
      const yearContainer = document.querySelector(`.year-checkbox[data-year="${year}"]`).closest('.year-item');
      const monthCheckboxes = yearContainer.querySelectorAll('.month-checkbox');

      const anyChecked = Array.from(monthCheckboxes).some(c => c.checked);
      const yearCheckbox = yearContainer.querySelector('.year-checkbox');
      yearCheckbox.checked = anyChecked;
      yearContainer.classList.toggle('selected-tag', anyChecked);

    });
  });
});

// reload page tag selecting memorizing
document.querySelectorAll('.tag-item input[type="checkbox"]').forEach(cb => {
  if (cb.checked) {
    cb.closest('.tag-item').classList.add('selected-tag');
  }
});

// reload page month selecting memorizing
document.querySelectorAll('.month-checkbox').forEach(cb => {
  if (cb.checked) {
    const label = cb.closest('label');
    if (label) label.classList.add('selected-tag');
  }
});

// reload page year selecting memorizing
document.querySelectorAll('.year-checkbox').forEach(cb => {
  if (cb.checked) {
    const yearItem = cb.closest('.year-item');
    yearItem.classList.add('selected-tag');
  }
});

document.addEventListener("DOMContentLoaded", () => {
  const sidebarWrapper = document.getElementById('sidebar-wrapper');
  const sidebarContainer = document.getElementById('sidebar-container');
  const toggleSidebarBtn = document.getElementById('toggle-sidebar');
  const overlay = document.getElementById('sidebar-overlay');

  const isMobile = () => window.innerWidth <= 768;

  const toggleSidebar = () => {
    const nowMobile = isMobile();

    if (nowMobile) {
      const isOpen = sidebarWrapper.classList.toggle('active');
      document.body.classList.toggle('sidebar-open', isOpen);
      toggleSidebarBtn.textContent = isOpen ? '✕' : '☰';
    } else {
      const isCollapsed = sidebarContainer.classList.toggle('collapsed');
      toggleSidebarBtn.textContent = isCollapsed ? '☰' : '✕';
      // no overlay or body lock for laptop view
      document.body.classList.remove('sidebar-open');
    }
  };

  if (toggleSidebarBtn) {
    toggleSidebarBtn.addEventListener('click', toggleSidebar);
  }

  if (overlay) {
    overlay.addEventListener('click', () => {
      sidebarWrapper.classList.remove('active');
      document.body.classList.remove('sidebar-open');
      toggleSidebarBtn.textContent = '☰';
    });
  }

  // reset sidebar if view adjusted to laptop width
  window.addEventListener('resize', () => {
    if (!isMobile()) {
      sidebarWrapper.classList.remove('active');
      document.body.classList.remove('sidebar-open');
      toggleSidebarBtn.textContent = sidebarContainer.classList.contains('collapsed') ? '☰' : '✕';
    }
  });
});

document.querySelectorAll('.month-checkbox:checked').forEach(cb => {
  const year = cb.dataset.year;
  const yearCheckbox = document.querySelector(`.year-item input[data-year="${year}"]`);
  const yearItem = yearCheckbox?.closest('.year-item');

  if (yearItem) {
    const children = yearItem.querySelector('.month-children');
    const toggleIcon = yearItem.querySelector('.toggle-icon');

    if (children) {
      children.style.display = 'block';
    }

    if (toggleIcon) {
      toggleIcon.classList.remove('collapsed');
    }

    if (yearCheckbox) {
      yearCheckbox.checked = true;
    }

    yearItem.classList.add('selected-tag');
  }
});



// 展開有勾選子標籤的父層標籤區塊
document.querySelectorAll('.tag-item input[type="checkbox"]:checked').forEach(cb => {
  const tagItem = cb.closest('.tag-item');
  const childrenContainer = tagItem?.closest('.tag-children');
  if (childrenContainer) {
    const parentItem = childrenContainer.previousElementSibling;
    const toggleIcon = parentItem?.querySelector('.toggle-icon');
    if (childrenContainer) {
      childrenContainer.style.display = 'block';
    }
    if (toggleIcon) {
      toggleIcon.classList.remove('collapsed');
    }
  }
});

document.querySelectorAll('.faq-question').forEach(button => {
  button.addEventListener('click', () => {
    const faqItem = button.closest('.faq-item');
    const answer = faqItem.querySelector('.faq-answer');
    const icon = button.querySelector('.toggle-icon');
    const isOpen = answer.style.display === 'block';

    // 先全部關閉（除了目前這個）
    document.querySelectorAll('.faq-item').forEach(item => {
      if (item !== faqItem) {
        item.querySelector('.faq-answer').style.display = 'none';
        item.querySelector('.toggle-icon').classList.add('collapsed');
      }
    });

    // 切換目前這一項
    if (isOpen) {
      answer.style.display = 'none';
      icon.classList.add('collapsed');
    } else {
      answer.style.display = 'block';
      icon.classList.remove('collapsed');
    }
  });
});

document.querySelectorAll('.thumbnail').forEach(thumbnail => {
  const photoBox = thumbnail.closest('.photo');
  thumbnail.addEventListener('mouseenter', () => {
    photoBox.classList.add('hovering');
  });
  thumbnail.addEventListener('mouseleave', () => {
    photoBox.classList.remove('hovering');
  });
});
document.querySelectorAll('.flip-back').forEach(backEl => {
  const descEl = backEl.querySelector('.photo-desc');

  // Run after DOM is ready or when the flip happens
  const updateAlignment = () => {
    requestAnimationFrame(() => {
      const isOverflowing = descEl.scrollHeight > backEl.clientHeight;
      backEl.classList.toggle('centered', !isOverflowing);
    });
  };

  // Initial check
  updateAlignment();

  // Optional: re-check on window resize
  window.addEventListener('resize', updateAlignment);
});

// PWA support
if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register('/service-worker.js')
    .then(reg => console.log('✅ Service Worker registered:', reg.scope))
    .catch(err => console.error('❌ Service Worker registration failed:', err));
}