(() => {
  const container = document.querySelector('#tinypng_dashboard_widget .inside');
  if (!container) {
    return;
  }

  const nonce = window.tinyDashboardWidget.nonce;
  const pollInterval = Number(window.tinyDashboardWidget.pollInterval);
  let timer = null;
  let requesting = false;

  // Get more credits is a second view in the same HTML. Nothing is stored, so
  // a reload shows the main view again.
  container.addEventListener('click', (event) => {
    const action = event.target.closest('[data-tiny-action]');
    if (!action) {
      return;
    }
    const showCredits = action.dataset.tinyAction === 'more-credits';
    container.querySelector('.tiny-widget-main').hidden = showCredits;
    container.querySelector('.tiny-widget-credits').hidden = !showCredits;
  });

  const isCompressing = () => {
    const widget = container.querySelector('.tiny-widget[data-state]');
    return widget !== null && widget.dataset.state === 'compressing';
  };

  // Poll only while compressing. Any failure stops the polling and leaves the
  // widget as it is.
  const poll = async () => {
    if (document.hidden || requesting) {
      return;
    }
    requesting = true;
    try {
      const response = await fetch(ajaxurl, {
        method: 'POST',
        credentials: 'same-origin',
        body: new URLSearchParams({ action: 'tiny_dashboard_widget', _nonce: nonce }),
      });
      if (!response.ok) {
        return;
      }
      const json = await response.json();
      if (!json.success) {
        return;
      }
      container.innerHTML = json.data.html;
      schedule();
    } catch (error) {
      // Stop polling.
    } finally {
      requesting = false;
    }
  };

  const schedule = () => {
    clearTimeout(timer);
    if (isCompressing()) {
      timer = setTimeout(poll, pollInterval);
    }
  };

  document.addEventListener('visibilitychange', () => {
    if (!document.hidden) {
      schedule();
    }
  });

  schedule();
})();
