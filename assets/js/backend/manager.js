/**
 * Copyright (c) 2025 Dave Beusing <david.beusing@gmail.com>
 *
 * MIT License - https://opensource.org/license/mit/
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A
 * PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
 * SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 */

/**


const form = document.getElementById('catalogForm');
const formFields = document.getElementById('formFields');
const dateFields = ['created', 'updated', 'eoldate', 'stocketa'];
const fields = [
  "status", "title", "description", "manufacturer", "gpsr", "mpn", "ean",
  "taric", "unspc", "eclass", "weeenr", "tax", "category1", "category2",
  "category3", "category4", "category5", "weight", "width", "depth", "height",
  "volweight", "iseol", "eoldate", "minorderqty", "maxorderqty", "copyrightcharge",
  "shipping", "sku", "iscondition", "availability", "stock", "stocketa", "price"
];

function formatDateTimeLocal(val) {
  if (!val) return '';
  const d = new Date(val);
  return d.toISOString().slice(0, 16);
}

function fillForm(data = {}) {
  form.classList.add('active');
  form.uid.readOnly = !!data.uid;
  form.uid.value = data.uid || '';
  formFields.innerHTML = '';
  fields.forEach(f => {
    const type = dateFields.includes(f) ? (f === 'stocketa' ? 'date' : 'datetime-local') : 'text';
    const val = data[f] || '';
    const inputVal = type.includes('date') ? formatDateTimeLocal(val) : val;
    formFields.innerHTML += `<label class="form-label">${f}<input name="${f}" type="${type}" value="${inputVal}"></label>`;
  });
}

function closeForm() {
  form.classList.remove('active');
  form.reset();
}

function newEntry() {
  fillForm();
}

function editEntry(uid) {
  fetch('catalog_api.php?action=load&uid=' + encodeURIComponent(uid))
    .then(r => r.json())
    .then(data => fillForm(data));
}

function deleteEntry() {
  if (!confirm('Wirklich löschen?')) return;
  fetch('catalog_api.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({ action: 'delete', uid: form.uid.value })
  }).then(() => location.reload());
}

form.onsubmit = e => {
  e.preventDefault();
  const data = {};
  new FormData(form).forEach((val, key) => data[key] = val);
  fetch('catalog_api.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify(data)
  }).then(() => location.reload());
};

*/


export default class manager {
	constructor( debug=false ){
		this.elements = {};
	}
	$( element ){
		return document.querySelector( element );
	}
	loadEntries(query = '', page = 1) {
		let currentQuery = query;
		let currentPage = page;
		let sortBy = 'updated';
		let sortDir = 'asc';
		const rowsPerPage = 10;
		fetch(`catalog_api.php?action=search&query=${encodeURIComponent(query)}&sort=${sortBy}&dir=${sortDir}&page=${page}&limit=${rowsPerPage}`)
			.then(r => r.json())
			.then(data => {
			const tbody = document.getElementById('catalogBody');
			tbody.innerHTML = '';
			data.rows.forEach(row => {
				const tr = document.createElement('tr');
				tr.innerHTML = `
				<td>${row.uid}</td>
				<td>${row.title}</td>
				<td>${row.status}</td>
				<td>${row.price}</td>
				<td>${row.stock}</td>
				`;
				tr.addEventListener( 'click', () => {
				editEntry('${row.uid}');
				});
				tbody.appendChild(tr);
			});
			this.renderPagination(data.total);
			});
	}
	renderPagination( totalRows ) {
		const rowsPerPage = 10;
		let currentPage = 1;//page
		const totalPages = Math.ceil(totalRows / rowsPerPage);
		const pagination = document.getElementById('pagination');
		pagination.innerHTML = '';

		if (totalPages <= 1) return;

		const prev = document.createElement('button');
		prev.textContent = '‹';
		prev.disabled = currentPage === 1;
		prev.onclick = () => loadEntries(currentQuery, currentPage - 1);
		pagination.appendChild(prev);

		const startPage = Math.max(1, currentPage - 2);
		const endPage = Math.min(totalPages, currentPage + 2);

		for (let i = startPage; i <= endPage; i++) {
			const btn = document.createElement('button');
			btn.textContent = i;
			btn.classList.toggle('active', i === currentPage);
			btn.onclick = () => loadEntries(currentQuery, i);
			pagination.appendChild(btn);
		}

		const next = document.createElement('button');
		next.textContent = '›';
		next.disabled = currentPage === totalPages;
		next.onclick = () => loadEntries(currentQuery, currentPage + 1);
		pagination.appendChild(next);
	}
	setSort(column, element) {
		const headers = document.querySelectorAll('th.sortable');
		headers.forEach(th => th.classList.remove('asc', 'desc'));
		if (sortBy === column) {
			sortDir = sortDir === 'asc' ? 'desc' : 'asc';
		} else {
			sortBy = column;
			sortDir = 'asc';
		}
		element.classList.add(sortDir);
		this.loadEntries(currentQuery, currentPage);
	}
	toggleColumn(index) {
		const table = document.getElementById('catalogTable');
		for (const row of table.rows) {
			if (row.cells[index]) row.cells[index].classList.toggle('hidden');
		}
	}
	run(){
		this.loadEntries();
		document.getElementById('searchInput').addEventListener('input', e => {
			this.loadEntries(e.target.value);
		});
	}
}