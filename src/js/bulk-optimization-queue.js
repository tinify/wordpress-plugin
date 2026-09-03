/*
 * Bulk Optimization Queue
 *
 * Starting sends one request that hands the library to the background queue;
 * from then on this script only polls for progress. The counts it renders come
 * from the queue status stored on each attachment, so they survive a reload,
 * a cancelled run, or a process that died halfway.
 */
(() => {
  const POLL_INTERVAL = 3000;

  const ICON_SUCCESS = '<span class="icon dashicons dashicons-yes success"></span>';
  const ICON_ERROR = '<span class="icon dashicons dashicons-no error"></span>';
  const ICON_ALERT = '<span class="icon dashicons dashicons-no alert"></span>';

  const renderedIds = new Set();
  let pollTimer = null;
  let el = null;

  async function request(action) {
    try {
      const response = await fetch(ajaxurl, {
        method: 'POST',
        credentials: 'same-origin',
        body: new URLSearchParams({ action, _nonce: tinyCompress.nonce }),
      });
      return response.ok ? await response.json() : null;
    } catch {
      return null;
    }
  }

  function statusLabel(state) {
    const label = {
      running: `${tinyCompress.L10nCompressing}…`,
      done: tinyCompress.L10nAllDone,
      cancelled: tinyCompress.L10nCancelled,
      stalled: tinyCompress.L10nInternalError,
      unreachable: tinyCompress.L10nQueueUnreachable,
    }[state.status];

    if (!label) {
      return '';
    }

    // The loopback error is the only thing that says why nothing is happening.
    return state.error_message ? `${label}: ${state.error_message}` : label;
  }

  function fillStatus(cell, item) {
    if (item.status === 'done') {
      let html = '';
      if (item.sizes_compressed > 0) {
        html += `<p>${ICON_SUCCESS} ${item.sizes_compressed} ${tinyCompress.L10nCompressed}</p>`;
      }
      if (item.sizes_converted > 0) {
        html += `<p>${ICON_SUCCESS} ${item.sizes_converted} ${tinyCompress.L10nConverted}</p>`;
      }
      cell.innerHTML = html;
      return;
    }

    if (item.status === 'failed') {
      cell.innerHTML = `${ICON_ERROR} ${tinyCompress.L10nError}`;
      if (item.message) {
        cell.append(document.createElement('br'), item.message);
      }
      return;
    }

    cell.innerHTML = `${ICON_ALERT} ${tinyCompress.L10nNoActionTaken}`;
  }

  function addCell(row, className) {
    const cell = row.insertCell();
    cell.className = className;
    return cell;
  }

  function buildRow(item) {
    const row = document.createElement('tr');
    row.className = 'media-item';

    addCell(row, 'thumbnail').innerHTML = item.thumbnail || '';
    addCell(row, 'column-primary name').textContent = item.title || item.id;
    addCell(row, 'column-author initial-size').textContent = item.initial_size || '-';
    addCell(row, 'column-author optimized-size').textContent = item.optimized_size || '-';
    addCell(row, 'column-author savings').textContent = item.savings ? `${item.savings}%` : '-';

    const status = addCell(row, 'column-author status');
    status.dataset.status = item.status;
    fillStatus(status, item);

    return row;
  }

  function renderLog(log) {
    const fresh = log.filter((item) => !renderedIds.has(item.id));
    if (fresh.length === 0) {
      return;
    }

    const batch = document.createDocumentFragment();
    for (const item of fresh.reverse()) {
      renderedIds.add(item.id);
      batch.append(buildRow(item));
    }
    el.tbody.prepend(batch);
  }

  function render(state) {
    // A permission failure comes back as a bare {error: ...} with no status.
    if (!state || !state.status) {
      return;
    }

    const counts = state.counts || {};
    const total = parseInt(state.total, 10) || 0;
    const processed = parseInt(state.processed, 10) || 0;
    const percentage = total > 0 ? Math.round((processed / total) * 100) : 0;

    el.total.textContent = total;
    el.processed.textContent = processed;
    el.percentage.textContent = `(${percentage}%)`;
    el.progressBar.style.width = `${percentage}%`;

    el.optimized.textContent = counts.done || 0;
    el.skipped.textContent = counts.skipped || 0;
    el.failed.textContent = counts.failed || 0;
    el.processing.textContent = counts.processing || 0;
    // Everything not yet given a status is waiting too, not just the marked rows.
    el.pending.textContent = Math.max(0, total - processed - (counts.processing || 0));

    el.state.textContent = statusLabel(state);

    renderLog(state.log || []);

    const running = state.status === 'running';
    el.start.disabled = running;
    el.cancel.disabled = !running;

    if (running) {
      schedulePoll();
    } else {
      stopPolling();
    }
  }

  function schedulePoll() {
    if (pollTimer) {
      return;
    }
    pollTimer = setTimeout(async () => {
      pollTimer = null;
      const state = await request('tiny_bulk_queue_status');
      if (state) {
        render(state);
      } else {
        schedulePoll();
      }
    }, POLL_INTERVAL);
  }

  function stopPolling() {
    clearTimeout(pollTimer);
    pollTimer = null;
  }

  async function start() {
    el.start.disabled = true;
    // Resets the table: a new run reports on a fresh set of attachments.
    renderedIds.clear();
    el.tbody.replaceChildren();
    render(await request('tiny_bulk_queue_start'));
  }

  async function cancel() {
    el.cancel.disabled = true;
    render(await request('tiny_bulk_queue_cancel'));
  }

  window.tinyBulkQueue = (state) => {
    el = {
      start: document.getElementById('tiny-queue-start'),
      cancel: document.getElementById('tiny-queue-cancel'),
      total: document.getElementById('tiny-queue-total'),
      processed: document.getElementById('tiny-queue-processed'),
      percentage: document.getElementById('tiny-queue-percentage'),
      optimized: document.getElementById('tiny-queue-optimized'),
      skipped: document.getElementById('tiny-queue-skipped'),
      failed: document.getElementById('tiny-queue-failed'),
      processing: document.getElementById('tiny-queue-processing'),
      pending: document.getElementById('tiny-queue-pending'),
      state: document.getElementById('tiny-queue-state'),
      progressBar: document.querySelector('#tiny-queue-progress-bar #progress-size'),
      tbody: document.querySelector('#tiny-queue-items tbody'),
    };

    el.start.addEventListener('click', start);
    el.cancel.addEventListener('click', cancel);
    render(state);
  };
})();
