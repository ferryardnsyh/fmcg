import { jsPDF } from "jspdf";
import html2canvas from "html2canvas";

const LOGO_URL = new URL("../assets/logo.png", import.meta.url).href;
const COMPANY_NAME = "CV. Putra Sumber Sari";
const CONTAINER_STYLE =
  "position:relative;width:820px;padding:25px 30px 50px;background:#fff;font-family:Arial,Helvetica,sans-serif;font-size:12px;color:#333;box-sizing:border-box";
const TABLE_STYLE = "width:100%;border-collapse:collapse;position:relative;z-index:1";
const WATERMARK_STYLE =
  "position:absolute;top:0;left:0;width:100%;height:100%;display:flex;flex-direction:column;align-items:center;justify-content:center;pointer-events:none;z-index:999;opacity:0.08";
const HEADER_STYLE = "text-align:center;margin-bottom:18px;position:relative;z-index:1";

function cellStyle(align, isHeader) {
  const textAlign = align === "end" ? "right" : align === "center" ? "center" : "left";
  const padding = isHeader ? "8px 10px" : "6px 10px";
  return `padding:${padding};border:1px solid #dee2e6;text-align:${textAlign};font-size:11px`;
}

function createCell(value, align, isHeader = false) {
  const cell = document.createElement(isHeader ? "th" : "td");
  cell.style.cssText = cellStyle(align, isHeader);
  cell.textContent = value === null || value === undefined ? "" : String(value);
  return cell;
}

function createHeaderRow(columns) {
  const row = document.createElement("tr");
  row.style.cssText = "background:#e9ecef";
  row.appendChild(createCell("No", "center", true));
  columns.forEach((column) => {
    row.appendChild(createCell(column.label, column.align, true));
  });
  return row;
}

function createDataRow(row, rowNumber, columns) {
  const tr = document.createElement("tr");
  tr.appendChild(createCell(rowNumber, "center"));
  columns.forEach((column) => {
    tr.appendChild(createCell(row ? row[column.key] : "", column.align));
  });
  return tr;
}

function createMeasureNode(columns) {
  const container = document.createElement("div");
  container.style.cssText = CONTAINER_STYLE;

  const heading = document.createElement("div");
  heading.style.cssText = HEADER_STYLE;
  const title = document.createElement("h2");
  title.style.cssText = "margin:0 0 4px;font-size:16px";
  title.textContent = "X";
  const subtitle = document.createElement("small");
  subtitle.style.cssText = "font-size:11px";
  subtitle.textContent = "X";
  heading.append(title, subtitle);
  container.appendChild(heading);

  const table = document.createElement("table");
  table.style.cssText = TABLE_STYLE;
  const thead = document.createElement("thead");
  thead.appendChild(createHeaderRow(columns));
  const tbody = document.createElement("tbody");
  table.append(thead, tbody);
  container.appendChild(table);

  return { container, tbody };
}

function createPageNode({ title, dateLabel, columns, rows, startNumber, logoDataUrl }) {
  const container = document.createElement("div");
  container.style.cssText = CONTAINER_STYLE;

  const watermark = document.createElement("div");
  watermark.style.cssText = WATERMARK_STYLE;
  const logo = document.createElement("img");
  logo.src = logoDataUrl;
  logo.style.cssText = "width:250px;margin-bottom:10px";
  const watermarkText = document.createElement("span");
  watermarkText.style.cssText = "font-size:28px;font-weight:bold";
  watermarkText.textContent = COMPANY_NAME;
  watermark.append(logo, watermarkText);
  container.appendChild(watermark);

  const heading = document.createElement("div");
  heading.style.cssText = HEADER_STYLE;
  const headingTitle = document.createElement("h2");
  headingTitle.style.cssText = "margin:0 0 4px;font-size:16px;font-weight:bold;color:#222";
  headingTitle.textContent = title;
  const headingDate = document.createElement("small");
  headingDate.style.cssText = "color:#888;font-size:11px";
  headingDate.textContent = `Dicetak pada: ${dateLabel}`;
  heading.append(headingTitle, headingDate);
  container.appendChild(heading);

  const table = document.createElement("table");
  table.style.cssText = TABLE_STYLE;
  const thead = document.createElement("thead");
  thead.appendChild(createHeaderRow(columns));
  const tbody = document.createElement("tbody");
  rows.forEach((row, index) => {
    tbody.appendChild(createDataRow(row, startNumber + index, columns));
  });
  table.append(thead, tbody);
  container.appendChild(table);

  return container;
}

function loadImageAsDataUrl(src) {
  return new Promise((resolve) => {
    const image = new Image();
    image.onload = () => {
      const canvas = document.createElement("canvas");
      canvas.width = image.width;
      canvas.height = image.height;
      canvas.getContext("2d").drawImage(image, 0, 0);
      resolve(canvas.toDataURL("image/png"));
    };
    image.onerror = () => resolve("");
    image.src = src;
  });
}

export async function exportReportPdf({ rows = [], columns = [], title = "Laporan", dateLabel = "", reportKey = "laporan" }) {
  if (!rows.length) return;

  const logoDataUrl = await loadImageAsDataUrl(LOGO_URL);
  const doc = new jsPDF("landscape", "mm", "a4");
  const pageW = doc.internal.pageSize.getWidth();
  const pageH = doc.internal.pageSize.getHeight();
  const margin = 8;
  const contentW = pageW - margin * 2;

  const { container: measureNode, tbody } = createMeasureNode(columns);
  document.body.appendChild(measureNode);
  let rowsPerPage;
  try {
    const baseCanvas = await html2canvas(measureNode, { scale: 2, backgroundColor: "#fff" });
    const baseHeight = baseCanvas.height / 2;
    tbody.replaceChildren(createDataRow({}, 1, columns));
    void measureNode.offsetHeight;
    const rowCanvas = await html2canvas(measureNode, { scale: 2, backgroundColor: "#fff" });
    const rowHeight = Math.max(1, rowCanvas.height / 2 - baseHeight);
    const availPx = (pageH - margin * 2) * (820 / contentW);
    rowsPerPage = Math.max(1, Math.floor((availPx - baseHeight - 20) / rowHeight));
  } finally {
    measureNode.remove();
  }

  let pageNum = 0;
  for (let i = 0; i < rows.length; i += rowsPerPage) {
    const pageRows = rows.slice(i, i + rowsPerPage);
    const pageNode = createPageNode({
      title,
      dateLabel,
      columns,
      rows: pageRows,
      startNumber: i + 1,
      logoDataUrl,
    });
    document.body.appendChild(pageNode);
    try {
      const canvas = await html2canvas(pageNode, {
        scale: 2,
        useCORS: true,
        letterRendering: true,
        backgroundColor: "#fff",
      });
      const imgW = pageW - margin * 2;
      const imgH = (canvas.height * imgW) / canvas.width;
      if (pageNum > 0) doc.addPage();
      doc.addImage(canvas.toDataURL("image/jpeg", 0.95), "JPEG", margin, margin, imgW, imgH);
      pageNum += 1;
    } finally {
      pageNode.remove();
    }
  }

  doc.save(`laporan-${reportKey}-${new Date().toISOString().slice(0, 10)}.pdf`);
}
