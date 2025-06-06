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

export default class manager {
	constructor( debug=false ){
		this.debug = debug;
		this.app ={
			baseURL : 'https://bsng.eu/app/fyndi'
		};
		this.ui = {
			searchInput : this.$( '#search-input' ),
			catalogItems : this.$( '#catalog-items' ),
			catalogItemsList : this.$( '#catalog-items-list' ),
			catalogItemForm : this.$( '#catalog-item-form' ),
			catalogItemFormFields : this.$( '#catalog-item-formfields' ),
			buttonCreateItem : this.$( '#create-item' ),
			buttonDeleteItem : this.$( '#delete-item' ),
			buttonAbort : this.$( '#abort' ),
			pagination : this.$( '#pagination' )
		};
		this.CatalogFields = {
			'fields' : [
				"status", "title", "description", "manufacturer", "gpsr", "mpn", "ean",
				"taric", "unspc", "eclass", "weeenr", "tax", "category1", "category2",
				"category3", "category4", "category5", "weight", "width", "depth", "height",
				"volweight", "iseol", "eoldate", "minorderqty", "maxorderqty", "copyrightcharge",
				"shipping", "sku", "iscondition", "availability", "stock", "stocketa", "price"
			],
			'dateFields' : ['created', 'updated', 'eoldate', 'stocketa']
		};
		this.sortables = [ 'uid', 'title', 'status', 'price', 'stock' ];
		this.sortBy = 'updated';
		this.sortDir = 'asc';
		this.currentQuery = '';
		this.currentPage = 1;
		this.rowsPerPage = 10;
	}
	$( element ){
		return document.querySelector( element );
	}
	loadEntries( query = '', page = 1 ) {
		this.currentQuery = query;
		this.currentPage = page;
		fetch(`${this.app.baseURL}/api/backend?action=search&query=${encodeURIComponent(query)}&sort=${this.sortBy}&dir=${this.sortDir}&page=${page}&limit=${this.rowsPerPage}`)
			.then( r => r.json() )
			.then( data => {
				const tbody = this.ui.catalogItemsList;
				tbody.innerHTML = '';
				data.rows.forEach( row => {
					const tr = document.createElement( 'tr' );
					tr.innerHTML = `
						<td>${row.uid}</td>
						<td>${row.title}</td>
						<td>${row.status}</td>
						<td>${row.price}</td>
						<td>${row.stock}</td>
					`;
					tr.addEventListener( 'click', () => {
						this.editEntry( `${row.uid}` );
					});
					tbody.appendChild( tr );
			});
			this.renderPagination( data.total, this.currentPage );
			});
	}
	editEntry( uid ) {
		fetch( `${this.app.baseURL}/api/backend?action=load&uid=${encodeURIComponent( uid )}` )
			.then( r => r.json() )
			.then( data => this.fillForm( data ) );
	}
	newEntry() {
		this.fillForm();
	}
	deleteEntry() {
		if( !confirm( 'Wirklich löschen?' ) ) return;
		fetch( `${this.app.baseURL}/api/backend`, {
		  method: 'POST',
		  headers: {'Content-Type': 'application/json'},
		  body: JSON.stringify({ action: 'delete', uid: this.ui.catalogItemForm.uid.value })
		})
		.then( () => location.reload() );
	}
	fillForm( data = {} ) {
		const form = this.ui.catalogItemForm;
		const formFields = this.ui.catalogItemFormFields;
		form.classList.add( 'active' );
		form.uid.readOnly = !!data.uid;
		form.uid.value = data.uid || '';
		formFields.innerHTML = '';
		this.CatalogFields.fields.forEach( field => {
			const type = this.CatalogFields.dateFields.includes( field ) ? ( field === 'stocketa' ? 'date' : 'datetime-local' ) : 'text';
			const val = data[field] || '';
			const inputVal = type.includes( 'date' ) ? this.formatDateTimeLocal( val ) : val;
			formFields.innerHTML += `<label class="form-label">${field}<input name="${field}" type="${type}" value="${inputVal}"></label>`;
		});
	}
	closeForm() {
		this.ui.catalogItemForm.classList.remove( 'active' );
		this.ui.catalogItemForm.reset();
	}
	formatDateTimeLocal( val ) {
		if (!val) return '';
		const d = new Date( val );
		return d.toISOString().slice( 0, 16 );
	  }
	renderPagination( totalRows, page ) {
		const rowsPerPage = 10;
		this.currentPage = page;
		const totalPages = Math.ceil(totalRows / rowsPerPage);
		const pagination = this.ui.pagination;
		pagination.innerHTML = '';
		if (totalPages <= 1) return;
		const prev = document.createElement('button');
		prev.textContent = '‹';
		prev.disabled = this.currentPage === 1;
		prev.addEventListener( 'click', event => {
			this.loadEntries( this.currentQuery, this.currentPage - 1 );
		});
		pagination.appendChild(prev);

		const startPage = Math.max( 1, this.currentPage - 2 );
		const endPage = Math.min( totalPages, this.currentPage + 2 );

		for( let i = startPage; i <= endPage; i++ ){
			const btn = document.createElement('button');
			btn.textContent = i;
			btn.classList.toggle( 'active', i === this.currentPage );
			btn.addEventListener( 'click', event => {
				this.loadEntries( this.currentQuery, i );
			} );
			pagination.appendChild(btn);
		}

		const next = document.createElement('button');
		next.textContent = '›';
		next.disabled = this.currentPage === totalPages;
		next.addEventListener( 'click', event => {
			this.loadEntries( this.currentQuery, this.currentPage + 1 );
		} );
		pagination.appendChild( next );
	}
	setSort( column, element ) {
		const headers = document.querySelectorAll( 'th.sortable' );
		headers.forEach( th => th.classList.remove( 'asc', 'desc' ) );
		if( this.sortBy === column ){
			this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
		} else {
			this.sortBy = column;
			this.sortDir = 'asc';
		}
		element.classList.add( this.sortDir );
		this.loadEntries( this.currentQuery, this.currentPage );
	}
	toggleColumn(index) {
		for( const row of this.ui.catalogItemsList.rows) {
			if( row.cells[index] ){
				row.cells[index].classList.toggle( 'hidden' );
			}
		}
	}
	run(){
		this.loadEntries();

		this.ui.searchInput.addEventListener( 'input', event => {
			this.loadEntries( event.target.value );
		} );

		this.ui.buttonCreateItem.addEventListener( 'click', event => {
			this.newEntry();
		} );

		this.ui.buttonDeleteItem.addEventListener( 'click', event => {
			this.deleteEntry();
		} );

		this.ui.buttonAbort.addEventListener( 'click', event => {
			this.closeForm();
		} );

		this.ui.catalogItemForm.addEventListener( 'submit', event => {
			event.preventDefault();
			const data = {};
			new FormData( this.ui.catalogItemForm ).forEach( ( val, key ) => data[key] = val );
			fetch( `${this.app.baseURL}/api/backend`, {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify( data )
			})
			.then( () => location.reload() );
		});

		this.sortables.forEach( ( val, key ) => {
			this.$(`#toggle-${val}`).addEventListener( 'change', event => {
				this.toggleColumn(key);
			} );
		} );

		this.sortables.forEach( ( val, key ) => {
			this.$(`#sort-${val}`).addEventListener( 'click', event => {
				this.setSort( val, this.$(`#sort-${val}`) );
			} );
		} );
		/* onclick="setSort('stock', this)" */

	}
}