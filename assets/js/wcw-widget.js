(() => {
  const config = window.wcwWidgetData || {};
  const widgets = document.querySelectorAll('.wcw-widget');
  if (!widgets.length) return;

  let shownByExitIntent = false;

  const revealWidget = (widget) => {
    widget.classList.add('wcw-ready');

    if (Number(config.pulseEnabled)) {
      widget.classList.add('wcw-pulse');
    }

    const tooltip = widget.querySelector('.wcw-tooltip');
    if (tooltip && Number(config.tooltipAutoHide)) {
      const hideMs = Math.max(1000, Number(config.tooltipHideSeconds || 5) * 1000);
      window.setTimeout(() => {
        tooltip.classList.add('wcw-hidden');
      }, hideMs);
    }
  };

  const setupWidget = (widget) => {
    const button = widget.querySelector('.wcw-button');
    const menu = widget.querySelector('.wcw-menu');
    const isMobile = /Android|iPhone|iPad|iPod|IEMobile|Opera Mini/i.test(navigator.userAgent || '');

    if (button && menu && button.dataset.hasMultiple === '1') {
      button.addEventListener('click', (event) => {
        event.preventDefault();
        menu.hidden = !menu.hidden;
      });
    } else if (button && isMobile && button.dataset.phone) {
      button.addEventListener('click', (event) => {
        event.preventDefault();
        const msg = encodeURIComponent(button.dataset.message || '');
        const phone = encodeURIComponent(button.dataset.phone);
        window.location.href = `whatsapp://send?phone=${phone}&text=${msg}`;
      });
    }

    if (button && Number(config.gaEventEnabled)) {
      button.addEventListener('click', () => {
        if (typeof window.gtag === 'function') {
          window.gtag('event', 'whatsapp_chat_click', {
            event_category: 'engagement',
            event_label: 'WhatsApp Widget'
          });
        }
      });
    }

    if (!Number(config.animationEnabled)) {
      widget.classList.add('wcw-ready');
      return;
    }

    const delay = Math.max(0, Number(config.delay || 0)) * 1000;
    window.setTimeout(() => revealWidget(widget), delay);
  };

  widgets.forEach(setupWidget);

  if (Number(config.exitIntentEnabled) && window.matchMedia('(pointer:fine)').matches) {
    document.addEventListener('mouseout', (event) => {
      if (shownByExitIntent) return;
      if (!event.relatedTarget && event.clientY <= 0) {
        shownByExitIntent = true;
        widgets.forEach(revealWidget);
      }
    });
  }
})();
