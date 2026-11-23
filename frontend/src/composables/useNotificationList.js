import { onBeforeUnmount, ref } from "vue";
import notificationService from "../services/notification";
import { DEBOUNCE } from "../constants/app";

export function useNotificationList({ limit = 20 } = {}) {
  const notifications = ref([]);
  const page = ref(1);
  const pages = ref(1);
  const total = ref(0);
  const unreadCount = ref(0);
  const loading = ref(false);
  const error = ref(null);
  const search = ref("");
  const filterType = ref("");
  const filterRead = ref("");
  let debounceTimer = null;

  function buildParams() {
    const params = { page: page.value, limit };
    if (search.value) params.search = search.value;
    if (filterType.value) params.type = filterType.value;
    if (filterRead.value !== "") params.is_read = filterRead.value;
    return params;
  }

  async function loadUnread() {
    try {
      const res = await notificationService.getUnread();
      if (res.data?.status) unreadCount.value = res.data.total || 0;
    } catch (err) {
      if (!error.value) error.value = err;
    }
  }

  async function loadPage(targetPage = 1) {
    clearTimeout(debounceTimer);
    page.value = targetPage;
    loading.value = true;
    error.value = null;
    try {
      const res = await notificationService.getAll(buildParams());
      if (!res.data?.status) throw new Error(res.data?.message || "Gagal memuat notifikasi");
      notifications.value = res.data.data || [];
      const pagination = res.data.pagination || {};
      pages.value = pagination.pages || 1;
      total.value = pagination.total || 0;
    } catch (err) {
      notifications.value = [];
      pages.value = 1;
      total.value = 0;
      error.value = err;
    } finally {
      loading.value = false;
    }
    await loadUnread();
  }

  function debounceSearch() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => loadPage(1), DEBOUNCE.SEARCH_MS);
  }

  async function markRead(notification) {
    if (notification.is_read) return true;
    const previousRead = notification.is_read;
    try {
      await notificationService.markRead(notification.id);
      notification.is_read = 1;
      unreadCount.value = Math.max(0, unreadCount.value - 1);
      error.value = null;
      return true;
    } catch (err) {
      notification.is_read = previousRead;
      error.value = err;
      return false;
    }
  }

  async function markAllRead() {
    const previousRead = notifications.value.map((notification) => notification.is_read);
    const previousUnread = unreadCount.value;
    try {
      await notificationService.markAllRead();
      notifications.value.forEach((notification) => {
        notification.is_read = 1;
      });
      unreadCount.value = 0;
      error.value = null;
      return true;
    } catch (err) {
      notifications.value.forEach((notification, index) => {
        notification.is_read = previousRead[index];
      });
      unreadCount.value = previousUnread;
      error.value = err;
      return false;
    }
  }

  async function generate() {
    try {
      const res = await notificationService.generate();
      error.value = null;
      return res.data?.message || "Notifikasi berhasil dihasilkan";
    } catch (err) {
      error.value = err;
      return null;
    }
  }

  onBeforeUnmount(() => clearTimeout(debounceTimer));

  return {
    notifications,
    page,
    pages,
    total,
    unreadCount,
    loading,
    error,
    search,
    filterType,
    filterRead,
    loadPage,
    debounceSearch,
    markRead,
    markAllRead,
    generate,
  };
}
