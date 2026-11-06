(function() {
  const POLL_INTERVAL = 2000;
  const POLL_WINDOW = 10;
  const PAGE_WORKERS = 5;
  const config = window.tinyBulk;
  const items = config.items;
  let pending = [];
  let pageWorkers = 0;
  let finished = 0;
  let compressed = 0;
  let savedBytes = 0;
  let creditsUsed = 0;

  function element(name) {
    return document.querySelector('[data-tiny-bulk="' + name + '"]');
  }

  function setText(name, text) {
    const target = element(name);
    if (target) {
      target.textContent = text;
    }
  }

  // Fills in %s, or %1$s, %2$s, like PHP's sprintf.
  function format(template) {
    const values = Array.prototype.slice.call(arguments, 1);
    let next = 0;
    return template.replace(/%(?:(\d)\$)?s/g, function(match, position) {
      return position ? values[position - 1] : values[next++];
    });
  }

  function plural(count, singular, pluralForm) {
    return format(count === 1 ? singular : pluralForm, count.toLocaleString());
  }

  function formatBytes(bytes) {
    const units = ['B', 'KB', 'MB', 'GB', 'TB'];
    let unit = 0;
    while (bytes >= 1024 && unit < units.length - 1) {
      bytes /= 1024;
      unit++;
    }
    return bytes.toLocaleString(undefined, { maximumFractionDigits: 1 }) + ' ' + units[unit];
  }

  async function post(action, data) {
    const body = new URLSearchParams(Object.assign({ _nonce: tinyCompress.nonce, action: action }, data));
    const response = await fetch(ajaxurl, { method: 'POST', body: body, credentials: 'same-origin' });
    if (!response.ok) {
      const error = new Error(response.statusText);
      error.status = response.status;
      throw error;
    }
    const json = await response.json();
    return json.data;
  }

  function showHero(state) {
    document.querySelectorAll('[data-tiny-bulk-hero]').forEach(function(hero) {
      hero.hidden = hero.getAttribute('data-tiny-bulk-hero') !== state;
    });
  }

  function drawRows() {
    const list = document.getElementById('tiny-bulk-items');
    if (!list) {
      return;
    }
    items.forEach(function(item, i) {
      const row = document.createElement('li');
      row.className = 'tiny-bulk-item';
      row.setAttribute('data-status', 'waiting');

      const thumbnail = document.createElement('img');
      thumbnail.loading = 'lazy';
      thumbnail.alt = '';
      if (item.thumbnail) {
        thumbnail.src = item.thumbnail;
      }

      const name = document.createElement('div');
      name.className = 'tiny-bulk-item-name';
      const title = document.createElement('strong');
      title.textContent = item.post_title;
      const details = document.createElement('span');
      details.textContent = item.file + ', ' +
        plural(item.sizes, tinyCompress.L10nSize, tinyCompress.L10nSizes) + ', ' +
        formatBytes(item.bytes);
      name.append(title, details);

      const status = document.createElement('span');
      status.className = 'tiny-bulk-item-status';
      status.setAttribute('data-testid', 'bulk-item-status-' + i);
      status.textContent = tinyCompress.L10nWaiting;

      row.append(thumbnail, name, status);
      list.append(row);
    });
  }

  function setRowStatus(i, status, text) {
    const row = document.getElementById('tiny-bulk-items').children[i];
    row.setAttribute('data-status', status);
    row.querySelector('.tiny-bulk-item-status').textContent = text;
    return row;
  }

  function applyResult(i, result) {
    finished++;
    if (result.failed > 0) {
      setRowStatus(i, 'error', result.message).title = result.message;
    } else if (parseInt(result.success, 10) === 0) {
      setRowStatus(i, 'no-action', tinyCompress.L10nNoActionTaken);
    } else {
      compressed++;
      savedBytes -= parseInt(result.size_change, 10);
      setRowStatus(i, 'compressed', format(
        tinyCompress.L10nSmaller,
        result.optimized_total_size,
        Math.round(result.savings) + '%'
      ));
    }
  }

  function secondsLeftText() {
    const sizesLeft = pending.reduce(function(sum, i) {
      return sum + items[i].sizes;
    }, 0);
    const seconds = Math.round(sizesLeft / config.workers);
    if (seconds < 60) {
      return tinyCompress.L10nLessThanMinute;
    }
    if (seconds < 90 * 60) {
      return plural(Math.round(seconds / 60), tinyCompress.L10nMinuteLeft, tinyCompress.L10nMinutesLeft);
    }
    return plural(Math.round(seconds / 3600), tinyCompress.L10nHourLeft, tinyCompress.L10nHoursLeft);
  }

  function updateNumbers() {
    const hasItems = items.length > 0;
    const itemsRunningPerc = hasItems ? Math.floor(finished / items.length * 100) : 0;
    const runPercentage = `${itemsRunningPerc}%`;
    setText('run-done', finished.toLocaleString());
    setText('run-percentage', runPercentage);
    element('run-bar').style.width = runPercentage;
    setText('run-status', secondsLeftText() + ' ' + format(
      tinyCompress.L10nRunStatus,
      formatBytes(savedBytes),
      plural(creditsUsed, tinyCompress.L10nCredit, tinyCompress.L10nCredits)
    ));

    const libraryCompressed = config.imagesOptimized + compressed;
    const libraryPercentage = Math.floor(libraryCompressed / config.imagesTotal * 100);
    setText('library-percentage', libraryPercentage.toLocaleString());
    setText('library-compressed', libraryCompressed.toLocaleString());
    setText('library-todo', format(tinyCompress.L10nToGo, (config.imagesRemaining - compressed).toLocaleString()));
    element('library-bar').style.width = libraryPercentage + '%';
    setText('waiting', pending.length.toLocaleString());

    const librarySaved = config.bytesSaved + savedBytes;
    setText('stat-compressed', libraryCompressed.toLocaleString());
    setText('stat-saved', formatBytes(librarySaved));
    setText('stat-average', Math.round(librarySaved / config.initialSize * 100) + '%');
    setText('done-saved', formatBytes(librarySaved));
  }

  // Shows the done hero when every image was compressed. Otherwise the run was
  // paused or stopped, some images failed, or the page had no images listed,
  // and the page reloads to show what is left.
  function finishRun() {
    if (items.length === 0 || pending.length > 0 || compressed < items.length) {
      location.reload();
      return;
    }
    const title = element('list-title');
    title.textContent = title.getAttribute('data-done');
    element('list-waiting').hidden = true;
    setText('library-todo', format(tinyCompress.L10nToCompress, '0'));
    showHero('done');
  }

  // The queue works through the images in the order they are listed, so only
  // the first few waiting ones need asking about.
  async function pollStatus() {
    const batch = pending.slice(0, POLL_WINDOW);
    try {
      const data = await post('tiny_bulk_queue_status', {
        ids: batch.map(function(i) { return items[i].ID; }).join(',')
      });
      const running = data.running || pageWorkers > 0;
      const finishedBefore = finished;
      creditsUsed = data.compression_count - config.compressionCount;

      batch.forEach(function(i) {
        const item = data.items[items[i].ID] || {};
        if (item.status === 'done' || item.status === 'failed') {
          applyResult(i, item.result);
          pending = pending.filter(function(p) { return p !== i; });
        } else if (item.status === 'processing') {
          setRowStatus(i, 'compressing', tinyCompress.L10nCompressing);
        }
      });
      updateNumbers();

      // A run can still be going when this page has no images waiting for it,
      // e.g. when the page loaded just as the run was finishing up.
      if (running || (pending.length > 0 && finished > finishedBefore)) {
        setTimeout(pollStatus, POLL_INTERVAL);
      } else {
        finishRun();
      }
    } catch (error) {
      if (error.status === 403) {
        setText('run-notice', tinyCompress.L10nInternalError);
      } else {
        setTimeout(pollStatus, POLL_INTERVAL);
      }
    }
  }

  /**
   * When the page cannot reach itself (loopbacks are not supported),
   * the page works through the queue one image per request until none are left
   */
  async function optimizeInPage() {
    try {
      const data = await post('tiny_bulk_queue_optimize_next');
      if (data.optimized) {
        optimizeInPage();
      } else {
        pageWorkers--;
      }
    } catch (error) {
      if (error.status === 403) {
        pageWorkers--;
      } else {
        setTimeout(optimizeInPage, POLL_INTERVAL);
      }
    }
  }

  function showRun() {
    const title = element('list-title');
    if (title) {
      title.textContent = title.getAttribute('data-compressing');
    }
    showHero('compressing');
    updateNumbers();
  }

  async function startRun() {
    pending = items.map(function(item, i) { return i; });
    showRun();
    try {
      const data = await post('tiny_bulk_queue_start');
      if (data.background) {
        setText('run-notice', tinyCompress.L10nBackgroundNotice);
      } else {
        setText('run-notice', tinyCompress.L10nInPageNotice);
        for (let i = 0; i < PAGE_WORKERS; i++) {
          pageWorkers++;
          optimizeInPage();
        }
      }
      pollStatus();
    } catch (error) {
      setText('run-notice', tinyCompress.L10nInternalError);
    }
  }

  async function pauseRun() {
    const button = element('pause');
    button.disabled = true;
    button.textContent = tinyCompress.L10nPausing;
    try {
      await post('tiny_bulk_queue_cancel');
    } catch (error) {
      button.disabled = false;
      button.textContent = tinyCompress.L10nPause;
      setText('run-notice', tinyCompress.L10nInternalError);
    }
  }

  // The dashboard widget opens this page with &start=1 and a nonce, which the
  // server checks. Start right away, then drop both from the URL so a reload
  // does not start again.
  function startFromUrl() {
    const url = new URL(location.href);
    if (url.searchParams.has('start')) {
      url.searchParams.delete('start');
      url.searchParams.delete('_wpnonce');
      history.replaceState(null, '', url);
    }
    if (config.startOnLoad && !config.running && element('start')) {
      startRun();
    }
  }

  drawRows();

  document.querySelectorAll('[data-tiny-bulk="start"]').forEach(function(button) {
    button.addEventListener('click', startRun);
  });
  if (element('pause')) {
    element('pause').addEventListener('click', pauseRun);
  }

  if (config.running) {
    pending = items.map(function(item, i) { return i; });
    setText('run-notice', tinyCompress.L10nBackgroundNotice);
    showRun();
    pollStatus();
  } else {
    startFromUrl();
  }
}).call();
