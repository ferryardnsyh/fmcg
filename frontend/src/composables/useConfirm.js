import Swal from "sweetalert2";

export async function confirmAction(options = {}) {
  const result = await Swal.fire({
    icon: options.icon || "warning",
    title: options.title || "Konfirmasi",
    text: options.text || "Lanjutkan aksi ini?",
    showCancelButton: true,
    confirmButtonText: options.confirmButtonText || "Ya",
    cancelButtonText: options.cancelButtonText || "Batal",
    reverseButtons: true,
    ...options.swal,
  });
  return result.isConfirmed;
}

export function useConfirm() {
  return { confirm: confirmAction };
}
