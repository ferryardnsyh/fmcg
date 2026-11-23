export function paginationRange(total, page, perPage) {
  if (!total) return { start: 0, end: 0 };
  return {
    start: (page - 1) * perPage + 1,
    end: Math.min(page * perPage, total),
  };
}

export function paginationPages(currentPage, totalPages, siblingCount = 2) {
  if (totalPages <= 1) return [1];

  const totalNumbers = siblingCount * 2 + 3;
  if (totalPages <= totalNumbers + 2) {
    return Array.from({ length: totalPages }, (_, i) => i + 1);
  }

  const start = Math.max(1, currentPage - siblingCount);
  const end = Math.min(totalPages, currentPage + siblingCount);

  const items = [];
  const addEllipsisBefore = start > 2;
  const addEllipsisAfter = end < totalPages - 1;

  items.push(1);
  if (addEllipsisBefore) items.push("...");
  for (let i = start; i <= end; i++) items.push(i);
  if (addEllipsisAfter) items.push("...");
  items.push(totalPages);

  return items;
}
