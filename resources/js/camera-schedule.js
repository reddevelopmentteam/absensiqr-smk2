const POLL_INTERVAL_MS = 5000;

export function watchCameraSchedule(url, onChange) {
    let disposed = false;
    let timer = null;
    let controller = null;
    let lastOpen = null;
    let generation = 0;

    const publish = (open) => {
        if (open === lastOpen) return;
        lastOpen = open;
        onChange(open);
    };

    const refresh = async () => {
        clearTimeout(timer);
        if (disposed || document.hidden) return;

        const requestGeneration = ++generation;
        const requestController = new AbortController();
        controller = requestController;
        try {
            const response = await fetch(url, {
                method: 'GET',
                cache: 'no-store',
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
                signal: requestController.signal,
            });
            const result = response.ok ? await response.json() : null;
            if (requestGeneration === generation) publish(result?.open === true);
        } catch {
            if (requestGeneration === generation && !disposed && !document.hidden) publish(false);
        } finally {
            if (controller === requestController) controller = null;
            if (requestGeneration === generation && !disposed && !document.hidden) {
                timer = window.setTimeout(refresh, POLL_INTERVAL_MS);
            }
        }
    };

    const onVisibilityChange = () => {
        if (document.hidden) {
            generation++;
            clearTimeout(timer);
            controller?.abort();
            publish(false);
            return;
        }

        void refresh();
    };

    document.addEventListener('visibilitychange', onVisibilityChange);
    void refresh();

    return () => {
        disposed = true;
        clearTimeout(timer);
        controller?.abort();
        document.removeEventListener('visibilitychange', onVisibilityChange);
    };
}
