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
		this.isHighighting = !this.isHighighting || true;
		this.elements = {
			search_button : this.$( '#search-button' ),
			search_input : this.$( '#search-input' ),
			search_results : this.$( '#search-results' ),
			search_clear : this.$( '#search-clear' ),
			highlight_toggle : this.$( '#highlight-toggle')
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
	toggleClearButtonVisibility() {
		if( this.elements.search_input.value.trim() ){
			this.elements.search_clear.classList.add( "visible" );
		} else {
			this.elements.search_clear.classList.remove( "visible" );
		}
	}
	renderSearchResults( items, query ){
		this.elements.search_results.innerHTML = "";
		items.forEach( ( item, index ) => {
			const card = document.createElement( "div" );
			card.className = "card";
			card.style.animationDelay = `${index * 0.1}s`;
			card.title = "Produktseite in neuem Tab öffnen";
			card.onclick = () => {
					window.open( `https://bsng.eu/app/fyndi/?view=item&uid=${item.uid}`, '_blank');
			};
			card.innerHTML = `
			<svg class="external-link-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M14 3h7v7h-2V6.41l-9.29 9.3-1.42-1.42 9.3-9.29H14V3z"/><path d="M5 5h4V3H5c-1.1 0-2 .9-2 2v4h2V5zm14 14h-4v2h4c1.1 0 2-.9 2-2v-4h-2v4zM5 19v-4H3v4c0 1.1.9 2 2 2h4v-2H5z"/></svg>
			<div class="card-content">
				<h2>${this.highlight( item.title, query )}</h2>
				<p>Hersteller: ${this.highlight( item.manufacturer, query )}</p>
				<p>SKU: ${this.highlight( item.sku, query )} UID: ${item.uid}</p>
				<p>${item.price}€ / Bestand: ${item.stock}</p>
				<p>Score: ${item.score}</p>
			</div>
			`;
			this.elements.search_results.appendChild( card );
		});
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
				else {
					this.renderSearchResults( data, query );
				}
			})
			.catch( error => {
				this.elements.search_results.innerHTML = `<div class="result-item">❌ Error: ${error.message}</div>`;
			});
	}
	run(){

		this.elements.search_input.addEventListener( 'input', () => {
			this.toggleClearButtonVisibility();
			if( this.debounce.active ){
				clearTimeout( this.debounce.timer );
				this.debounce.timer = setTimeout( () => {
					this.fetchResults();
				}, this.debounce.delay );
			}
			else {
				this.fetchResults();
			}
		});


		this.elements.highlight_toggle.addEventListener( 'change', () => {
			this.elements.search_input.classList.toggle( "highlighted", this.elements.highlight_toggle.checked );
		});

		this.elements.search_button.addEventListener( 'click', () => {
			this.fetchResults();
		});

		this.elements.search_clear.addEventListener( 'click', () => {
			this.elements.search_input.value = '';
			this.elements.search_results.innerHTML = '';
			this.toggleClearButtonVisibility();
		});

		this.toggleClearButtonVisibility();
	}
}