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
export default class DashboardManager {
	constructor( debug=flase ){
		this.app ={
			baseURL : 'https://bsng.eu/app/fyndi'
		};
	}
	$( element ){
		return document.querySelector( element );
	}
	fetchMetrics(){
		fetch( `${this.app.baseURL}/api/backend?action=metrics` )
			.then( response => response.json() )
			.then( data => {
				const statEl = this.$( '#catalog-metrics' );
				statEl.innerHTML = `
					<ul>
						<li><strong>Products total:</strong> ${data.total}</li>
						<li><strong>in Stock:</strong> ${data.stocked}</li>
						<li><strong>End-of-Life:</strong> ${data.eol}</li>
						<li><strong>out of Stock:</strong> ${data.unavailable}</li>
						<li><strong>Ø Price:</strong> ${data.avg_price} €</li>
						<li><strong>Stock Value:</strong> ${data.total_stock_value} €</li>
						<li><strong>Ø Weight:</strong> ${data.avg_weight} kg</li>
						<li><strong>Ø Volumetric Weight:</strong> ${data.avg_volweight} kg</li>
						<li><strong>New (7d):</strong> ${data.created_7d}</li>
					</ul>
					<h3>Top-Categories</h3>
					<ul>
						${data.top_categories.map(c => `<li>${c.category1} (${c.count})</li>`).join('')}
					</ul>
					<h3>Availability</h3>
					<ul>
						${data.availability_dist.map(a => `<li>Status ${a.availability}: ${a.count}</li>`).join('')}
					</ul>
				`;
			} )
			.catch( error => {
				this.$( '#catalog-metrics' ).innerText = 'Failed loading catalog metrics';
				console.error( error );
			});
	}
	init(){
		this.fetchMetrics();
	}
}