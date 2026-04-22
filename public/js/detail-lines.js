/**
 * DetailLines Component Logic for Alxarafe SPA 0.6.1+
 * Replaces the legacy Blade rendering with native JS rendering and recalculation logic.
 */

// 1. Expose initDetailLines globally
window.initDetailLines = function(tableId) {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    const containerField = tableId.replace('table-', '');
    const tbody = document.getElementById('tbody-' + containerField);
    const addBtn = document.querySelector('.btn-add-line[data-target="' + containerField + '"]');
    const template = document.getElementById('template-' + containerField);
    
    // Prevent double binding
    if (table.dataset.initialized) return;
    table.dataset.initialized = "true";

    let newIndexCounter = Date.now();
    
    // Add Row Handler
    if (addBtn && template) {
        addBtn.addEventListener('click', function() {
            const html = template.innerHTML.replace(/__INDEX__/g, 'new_' + newIndexCounter++);
            tbody.insertAdjacentHTML('beforeend', html);
            
            const emptyState = tbody.querySelector('.empty-state-row');
            if (emptyState) emptyState.remove();
            
            const newRow = tbody.lastElementChild;
            const firstInput = newRow.querySelector('input:not([type="hidden"]), select, textarea');
            if (firstInput) firstInput.focus();
            
            updateOrderInputs(tbody);
        });
    }
    
    // Remove Row Handler
    tbody.addEventListener('click', function(e) {
        const removeBtn = e.target.closest('.btn-remove-line');
        if (removeBtn) {
            const row = removeBtn.closest('tr');
            const deleteInput = row.querySelector('.line-delete-input');
            if (deleteInput) {
                deleteInput.value = '1';
                row.style.display = 'none';
                row.classList.add('is-deleted');
            } else {
                row.remove();
            }
            recalculateTotals(table);
        }
    });
    
    // Auto-Recalculate
    tbody.addEventListener('input', function(e) {
        if (e.target.tagName === 'INPUT' && (e.target.type === 'number' || e.target.type === 'text')) {
            recalculateTotals(table);
        }
    });
    
    // Initial recalculation
    recalculateTotals(table);
};

// Recalculation Logic
function recalculateTotals(table) {
    const containerField = table.id.replace('table-', '');
    const rows = table.querySelectorAll('.detail-lines-body tr.detail-line-row:not(.is-deleted)');
    
    let totals = { pvptotal: 0, subtotal: 0, iva: 0, cantidad: 0 };
    
    rows.forEach(row => {
        const inputs = Array.from(row.querySelectorAll('input'));
        const findVal = (suffix) => {
            const input = inputs.find(i => i.name.endsWith('[' + suffix + ']'));
            return input ? (parseFloat(input.value) || 0) : 0;
        };
        
        const cant = findVal('cantidad');
        const price = findVal('pvpunitario');
        const dto = findVal('dtopor');
        const tax = findVal('iva');
        
        if (cant > 0 || price > 0) {
            let lineNet = (cant * price);
            lineNet = lineNet - (lineNet * (dto / 100));
            let lineTax = lineNet * (tax / 100);
            let lineTotal = lineNet + lineTax;
            
            const totalInput = inputs.find(i => i.name.endsWith('[pvptotal]'));
            if (totalInput) totalInput.value = lineTotal.toFixed(2);
            
            totals.subtotal += lineNet;
            totals.iva += lineTax;
            totals.pvptotal += lineTotal;
            totals.cantidad += cant;
        }
    });
    
    // Global doc updates
    const netoInput = document.querySelector('input[name="neto"]');
    const ivaInput = document.querySelector('input[name="totaliva"]');
    const docTotalInput = document.querySelector('input[name="total"]');
    
    if (netoInput) netoInput.value = totals.subtotal.toFixed(2);
    if (ivaInput) ivaInput.value = totals.iva.toFixed(2);
    if (docTotalInput) docTotalInput.value = totals.pvptotal.toFixed(2);
    
    // Footer spans
    for (const [key, val] of Object.entries(totals)) {
        const span = document.getElementById('total-' + key + '-' + containerField);
        if (span) span.textContent = val.toFixed(2);
    }
}

function updateOrderInputs(tbody) {
    const rows = tbody.querySelectorAll('tr.detail-line-row:not(.is-deleted)');
    rows.forEach((row, index) => {
        const orderInput = row.querySelector('.line-order-input');
        if (orderInput) orderInput.value = index * 10;
    });
}

// 2. Monkey-patch AlxarafeResource to render DetailLines
document.addEventListener('DOMContentLoaded', function() {
    if (typeof AlxarafeResource !== 'undefined') {
        const originalRenderField = AlxarafeResource.prototype.renderField;
        
        AlxarafeResource.prototype.renderField = function(field, value) {
            if (field.component === 'detail_lines') {
                return renderDetailLinesHTML(field, value, this);
            }
            return originalRenderField.call(this, field, value);
        };
    }
});

// HTML Generator for DetailLines matching the legacy UI
function renderDetailLinesHTML(field, rows, ctx) {
    const cols = field.options?.columns || [];
    const tableId = `table-${field.field}`;
    const trans = (k) => ctx.trans ? ctx.trans(k, k) : k;
    
    const renderHeader = () => {
        let h = '';
        if (field.options?.sortable) h += `<th style="width: 40px; text-align: center;"><i class="fas fa-sort text-muted"></i></th>`;
        cols.forEach(col => {
            const label = col.label || col.field;
            h += `<th>${trans(label)}</th>`;
        });
        if (field.options?.removeRow) h += `<th style="width: 50px;"></th>`;
        return h;
    };

    const renderRow = (row, index) => {
        let r = `<tr class="detail-line-row">`;
        if (field.options?.sortable) r += `<td class="text-center align-middle"><i class="fas fa-grip-vertical text-muted handle cursor-move"></i><input type="hidden" name="${field.field}[${index}][orden]" class="line-order-input" value="${index*10}"></td>`;
        
        cols.forEach(col => {
            const f = col.field;
            const val = row && row[f] !== undefined && row[f] !== null ? row[f] : '';
            const inputName = `${field.field}[${index}][${f}]`;
            const type = col.type || col.component || 'text';
            const readonlyAttr = col.readonly || (col.options && col.options.readonly) ? 'readonly tabindex="-1"' : '';
            
            let inputHtml = `<input type="${type === 'decimal' || type === 'number' ? 'number' : 'text'}" 
                                    step="${type === 'decimal' ? '0.01' : 'any'}"
                                    class="form-control form-control-sm" 
                                    name="${inputName}" 
                                    value="${val}" 
                                    ${readonlyAttr}>`;
            
            r += `<td>${inputHtml}</td>`;
        });
        
        if (field.options?.removeRow) {
            r += `<td class="text-center align-middle">
                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-line"><i class="fas fa-trash"></i></button>
                    <input type="hidden" name="${field.field}[${index}][delete]" class="line-delete-input" value="0">
                  </td>`;
        }
        
        const idInput = (row && row.id) ? `<input type="hidden" name="${field.field}[${index}][id]" value="${row.id}">` : '';
        r += `${idInput}</tr>`;
        return r;
    };

    const rowsArray = Array.isArray(rows) ? rows : Object.values(rows || {});
    let bodyHtml = rowsArray.length > 0 ? rowsArray.map((r, i) => renderRow(r, i)).join('') : 
                   `<tr class="empty-state-row"><td colspan="100%" class="text-center text-muted p-4">${trans('no_lines_found')}</td></tr>`;

    const templateHtml = renderRow({}, '__INDEX__');

    let tfootHtml = '';
    const footerTotals = field.options?.footerTotals;
    if (footerTotals && Object.keys(footerTotals).length > 0) {
        let foot = '<tfoot class="bg-light"><tr>';
        if (field.options?.sortable) foot += '<th></th>';
        const colspan = cols.length - Object.keys(footerTotals).length;
        if (colspan > 0) foot += `<td colspan="${colspan}" class="text-end fw-bold align-middle">${trans('totals')}:</td>`;
        
        cols.forEach((col, idx) => {
            if (idx >= colspan) {
                const fName = col.field;
                if (footerTotals[fName]) {
                    foot += `<td class="fw-bold fs-5 text-end"><span id="total-${fName}-${field.field}">0.00</span></td>`;
                } else {
                    foot += `<td></td>`;
                }
            }
        });
        if (field.options?.removeRow) foot += `<td></td>`;
        foot += '</tr></tfoot>';
        tfootHtml = foot;
    }

    let addBtn = '';
    if (field.options?.addRow) {
        addBtn = `<button type="button" class="btn btn-sm btn-primary btn-add-line" data-target="${field.field}"><i class="fas fa-plus"></i> ${trans('add_line')}</button>`;
    }

    const html = `
        <div class="col-12 mb-3" id="container-${field.field}">
            <div class="card shadow-sm border-secondary detail-lines-card">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0"><i class="fas fa-list me-2"></i> ${trans(field.label)}</h5>
                    ${addBtn}
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0 detail-lines-table" id="${tableId}">
                            <thead><tr>${renderHeader()}</tr></thead>
                            <tbody class="detail-lines-body" id="tbody-${field.field}">${bodyHtml}</tbody>
                            <template id="template-${field.field}">${templateHtml}</template>
                            ${tfootHtml}
                        </table>
                    </div>
                </div>
            </div>
        </div>
    `;

    setTimeout(() => {
        window.initDetailLines(tableId);
    }, 100);

    return html;
}
