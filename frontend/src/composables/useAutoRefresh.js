import { onBeforeUnmount, onMounted } from "vue";
import { POLLING } from "../constants/app";

export const DASHBOARD_POLL_MS = POLLING.DASHBOARD_MS || 5000;

const subscribers = new Set();
let timer = null;
let timerInterval = DASHBOARD_POLL_MS;

function tick() {
  subscribers.forEach((run) => {
    run();
  });
}

function startTimer(intervalMs) {
  if (timer) return;
  timerInterval = intervalMs;
  timer = setInterval(tick, timerInterval);
}

function stopTimer() {
  if (!timer || subscribers.size > 0) return;
  clearInterval(timer);
  timer = null;
}

export function useAutoRefresh(tasks, options = {}) {
  const jobList = Array.isArray(tasks) ? tasks : [tasks];
  const onError = typeof options.onError === "function" ? options.onError : () => {};
  const intervalMs = options.intervalMs || timerInterval || DASHBOARD_POLL_MS;
  let disposed = false;

  const runners = jobList
    .filter((task) => typeof task === "function")
    .map((task) => {
      let busy = false;
      return async () => {
        if (disposed || busy) return;
        busy = true;
        try {
          await task();
        } catch (error) {
          if (!disposed) onError(error);
        } finally {
          busy = false;
        }
      };
    });

  const refresh = () => {
    runners.forEach((run) => {
      run();
    });
  };

  onMounted(() => {
    runners.forEach((run) => subscribers.add(run));
    startTimer(intervalMs);
    refresh();
  });

  onBeforeUnmount(() => {
    disposed = true;
    runners.forEach((run) => subscribers.delete(run));
    stopTimer();
  });

  return { refresh };
}
