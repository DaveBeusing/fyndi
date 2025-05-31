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
export default class fyndi {
	constructor( debug=false ){
		this.debug = debug;
		this.api = 'index.php?view=api&query=';
		this.elements = {
			search_form : this.$( '#search-form' ),
			search_input : this.$( '#search-input' ),
			search_results : this.$( '#search-results' ),
			search_clear : this.$( '#search-clear' ),
		};
		this.debounce = {
			'active' : true,
			'delay' : 400,
			'timer' : false,
		};
	};
	$( element ){
		return document.querySelector( element );
	}
	highlight( str, term ){
		const escaped = term.replace( /[.*+?^${}()|[\]\\]/g, '\\$&' );
		const regex = new RegExp( `(${escaped})`, 'gi' );
		return str.replace( regex, '<mark>$1</mark>' );
	}
	fetchResults(){
		const query = this.elements.search_input.value.trim();
		if( query.length < 2 ){
			this.elements.search_results.innerHTML = '';
			return;
		}
		fetch( `${this.api}${encodeURIComponent( query )}` )
			.then( response => {
				if( !response.ok ) throw new Error( 'Network error' );
				return response.json();
			})
			.then( data => {
				this.elements.search_results.innerHTML = '';
				if( !Array.isArray( data ) || data.length === 0 ){
					this.elements.search_results.innerHTML = '<div class="result-item">Keine Ergebnisse gefunden.</div>';
					return;
				}
				data.forEach( item => {
					const div = document.createElement( 'div' );
					div.className = 'result-item';
					div.innerHTML = `
						<strong>${this.highlight( item.title, query )}</strong><br>
						Hersteller: ${this.highlight( item.manufacturer, query )}<br>
						SKU: ${this.highlight( item.sku, query )}<br>
						UID: ${item.uid}<br>
						Score: ${item.score}
					`;
					this.elements.search_results.appendChild( div );
				});
			})
			.catch( error => {
				this.elements.search_results.innerHTML = `<div class="result-item">❌ Error: ${error.message}</div>`;
			});
	}
	run(){

		if( this.debounce.active ){
			this.elements.search_input.addEventListener( 'input', () => {
				clearTimeout( this.debounce.timer );
				this.debounce.timer = setTimeout( () => {
					this.fetchResults();
				}, this.debounce.delay );
			});
		}

		this.elements.search_form.addEventListener( 'submit', event => {
			event.preventDefault();
			this.fetchResults();
		});

		this.elements.search_clear.addEventListener( 'click', () => {
			this.elements.search_input.value = '';
			this.elements.search_input.focus();
			this.elements.search_results.innerHTML = '';
		});

	}
}