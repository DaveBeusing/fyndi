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

export default class UserManager {
	constructor( debug=flase ){
		this.debug = debug;
		this.app ={
			baseURL : 'https://bsng.eu/app/fyndi'
		};
		this.ui = {
			userTable : this.$( '#userTable' )
		};
	}
	$( element ){
		return document.querySelector( element );
	}
	fetchUsers(){
		fetch( `${this.app.baseURL}/api/backend?action=users` )
			.then( response => response.json() )
			.then( data => {
				const tbody = this.ui.userTable;
				tbody.innerHTML = '';
				data.users.forEach( user => {
					const tr = document.createElement( 'tr' );
					tr.innerHTML = `
						<td>${user.id}</td>
						<td>${user.username}</td>
						<td>
							<select class="user-role" data-id="${user.id}">
							<option value="user" ${user.role === 'user' ? 'selected' : ''}>User</option>
							<option value="editor" ${user.role === 'editor' ? 'selected' : ''}>Editor</option>
							<option value="admin" ${user.role === 'admin' ? 'selected' : ''}>Admin</option>
							</select>
						</td>
					`;
					tbody.appendChild( tr );
				} );
			} );
	}
	createUser(){
		const formData = new FormData( this.$( '#createUserForm' ) );
		formData.append( 'action', 'createuser' );
		fetch( `${this.app.baseURL}/api/backend`, {
			method: 'POST',
			body: formData
		} )
		.then( response => response.json() )
		.then( data => {
			const msg = this.$( '#userMessage' );
			if( data.success ){
				msg.textContent = 'Benutzer erfolgreich erstellt';
				msg.style.color = '#065f46';
				this.fetchUsers();
			}
			else {
				msg.textContent = data.error || 'Fehler beim Erstellen';
				msg.style.color = '#b91c1c';
			}
		});
	}
	init(){
		this.fetchUsers();
		this.$( '#createUserForm' ).addEventListener( 'submit', event => {
			event.preventDefault();
			this.createUser();
		} );
	}
}