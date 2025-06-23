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
	formatPrice( price ){
		return new Intl.NumberFormat( "de-DE", { style: "currency", currency: "EUR" } ).format( price );
	}
	calculatePercentage( base, pval ){
		// p = P / G
		return (pval/base*100).toFixed(2);
	}
	fetchMetrics(){
		fetch( `${this.app.baseURL}/api/backend?action=metrics` )
			.then( response => response.json() )
			.then( data => {
				const statEl = this.$( '#catalog-metrics' );
				statEl.innerHTML = `
					<ul>
						<li><strong>Products total:</strong> ${data.total}</li>
						<li><strong>in Stock:</strong> ${data.stocked} ${this.calculatePercentage( data.total, data.stocked )}%</li>
						<li><strong>End-of-Life:</strong> ${data.eol} ${this.calculatePercentage( data.total, data.eol )}%</li>
						<li><strong>out of Stock:</strong> ${data.unavailable} ${this.calculatePercentage( data.total, data.unavailable )}%</li>
						<li><strong>Virtual:</strong> ${data.virtual} ${this.calculatePercentage( data.total, data.virtual )}%</li>
						<li><strong>Ø Price:</strong> ${this.formatPrice(data.avg_price)}</li>
						<li><strong>Stock Value:</strong> ${this.formatPrice(data.total_stock_value)}</li>
						<li><strong>Ø Weight:</strong> ${data.avg_weight} kg</li>
						<li><strong>Ø Volumetric Weight:</strong> ${data.avg_volweight} kg</li>
						<li><strong>New (7d):</strong> ${data.created_7d}</li>
						<li><strong>Manufacturers:</strong> ${data.unique_manufacturers}</li>
						<li><strong>Categories:</strong> ${data.unique_categories}</li>
						<li><strong>Missing MPN:</strong> ${data.missing_mpn} ${this.calculatePercentage( data.total, data.missing_mpn )}%</li>
						<li><strong>Missing EAN:</strong> ${data.missing_ean} ${this.calculatePercentage( data.total, data.missing_ean )}%</li>
						<li><strong>Missing TARIC:</strong> ${data.missing_taric} ${this.calculatePercentage( data.total, data.missing_taric )}%</li>
						<li><strong>Refurbished:</strong> ${data.refurbished} ${this.calculatePercentage( data.total, data.refurbished )}%</li>
						<li><strong>Shipping Parcel:</strong> ${data.shipping_parcel} ${this.calculatePercentage( data.total, data.shipping_parcel )}%</li>
						<li><strong>Shipping Bulk:</strong> ${data.shipping_bulk} ${this.calculatePercentage( data.total, data.shipping_bulk )}%</li>
						<li><strong>Shipping ePal:</strong> ${data.shipping_epal} ${this.calculatePercentage( data.total, data.shipping_epal )}%</li>
					</ul>
					<h3>Top-Categories</h3>
					<ul>
						${data.top_categories.map(c => `<li>${c.category1} (${c.count}) ${this.calculatePercentage( data.total, c.count )}%</li>`).join('')}
					</ul>
					<h3>Top-Manufacturers</h3>
					<ul>
						${data.top_manufacturers.map(c => `<li>${c.manufacturer} (${c.count}) ${this.calculatePercentage( data.total, c.count )}%</li>`).join('')}
					</ul>
					<h3>Availability</h3>
					<ul>
						${data.availability_dist.map(a => `<li>Status ${a.availability}: ${a.count}</li>`).join('')}
					</ul>
				`;

				// Categories
				const catLabels = data.top_categories.map(c => c.category1);
				const catCounts = data.top_categories.map(c => c.count);

				new Chart( this.$( '#categoryChart' ), {
					type: 'bar',
					data: {
						labels: catLabels,
						datasets: [{
							label: 'Products per Category',
							data: catCounts,
							backgroundColor: 'rgba(59, 130, 246, 0.6)',
							borderColor: 'rgba(37, 99, 235, 1)',
							borderWidth: 1
						}]
					},
					options: {
						responsive: true,
						plugins: {
						legend: { display: false }
						},
						scales: {
							y: {
								beginAtZero: true,
								ticks: { precision: 0 }
							}
						}
					}
				});

				// Availability
				const availLabels = data.availability_dist.map(a => 'Status ' + a.availability);
				const availCounts = data.availability_dist.map(a => a.count);

				new Chart( this.$( '#availabilityChart' ), {
					type: 'pie',
					data: {
						labels: ['Out-of-Stock', 'Available', 'Incoming'],
						datasets: [{
							label: 'Availability',
							data: availCounts,
							backgroundColor: [
								'rgba(239,68,68,0.6)',
								'rgba(34,197,94,0.6)',
								'rgba(234,179,8,0.6)',
								'rgba(59,130,246,0.6)'
							]
						}]
					},
					options: {
						responsive: true
					}
				});

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