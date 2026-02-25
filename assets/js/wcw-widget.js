(() => {
  const config = window.wcwWidgetData || {};
  const widgets = document.querySelectorAll('.wcw-widget');
  if (!widgets.length) return;

  let shownByExitIntent = false;

  const openWhatsApp = (phone, message) => {
    const safePhone = encodeURIComponent((phone || '').replace(/\D+/g, ''));
    const safeMessage = encodeURIComponent(message || '');
    if (!safePhone) return;

    const isMobile = /Android|iPhone|iPad|iPod|IEMobile|Opera Mini/i.test(navigator.userAgent || '');
    const waWeb = `https://wa.me/${safePhone}?text=${safeMessage}`;

    if (isMobile) {
      window.location.href = `whatsapp://send?phone=${safePhone}&text=${safeMessage}`;
      window.setTimeout(() => {
        window.open(waWeb, '_blank', 'noopener');
      }, 450);
      return;
    }

    window.open(waWeb, '_blank', 'noopener');
  };

  const revealWidget = (widget) => {
    widget.classList.remove('wcw-animate-init');
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

  const setPanelState = (widget, isOpen) => {
    const panel = widget.querySelector('.wcw-panel');
    const button = widget.querySelector('.wcw-button');
    if (!panel || !button) return;

    widget.classList.toggle('is-open', isOpen);
    panel.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
    button.setAttribute('aria-expanded', isOpen ? 'true' : 'false');

    if (isOpen) {
      const input = panel.querySelector('.wcw-input');
      if (input) input.focus();
    }
  };

  const setupWidget = (widget) => {
    const button = widget.querySelector('.wcw-button');
    const form = widget.querySelector('.wcw-form');
    const panel = widget.querySelector('.wcw-panel');

    if (button && panel) {
      button.addEventListener('click', () => {
        setPanelState(widget, !widget.classList.contains('is-open'));
      });
    }

    if (form) {
      form.addEventListener('submit', (event) => {
        event.preventDefault();

        const messageField = form.querySelector('.wcw-input');
        const phone = button?.dataset.defaultPhone || '';
        const message = (messageField?.value || button?.dataset.defaultMessage || '').trim();

        openWhatsApp(phone, message);
        setPanelState(widget, false);

        if (Number(config.gaEventEnabled) && typeof window.gtag === 'function') {
          window.gtag('event', 'whatsapp_chat_click', {
            event_category: 'engagement',
            event_label: 'WhatsApp Widget'
          });
        }
      });
    }

    document.addEventListener('click', (event) => {
      if (!widget.contains(event.target)) {
        setPanelState(widget, false);
      }
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        setPanelState(widget, false);
      }
    });

    if (!Number(config.animationEnabled)) {
      widget.classList.add('wcw-ready');
      return;
    }

    widget.classList.add('wcw-animate-init');
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
