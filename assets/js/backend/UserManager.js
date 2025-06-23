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
						<td>${user.uid}</td>
						<td>${user.email}</td>
						<td>
							<select class="user-role" data-id="${user.uid}">
							<option value="user" ${user.role === 'user' ? 'selected' : ''}>User</option>
							<option value="editor" ${user.role === 'editor' ? 'selected' : ''}>Editor</option>
							<option value="admin" ${user.role === 'admin' ? 'selected' : ''}>Admin</option>
							</select>
						</td>
						<td>
							<select class="user-status" data-id="${user.status}">
							<option value="user" ${user.status === 0 ? 'selected' : ''}>Inactive</option>
							<option value="editor" ${user.status === 1 ? 'selected' : ''}>Active</option>
							<option value="admin" ${user.status === 2 ? 'selected' : ''}>Suspended</option>
							</select>
						</td>
						<td>${user.login_attempts}</td>
						<td>${user.created_at}</td>
						<td>${user.updated_at}</td>
						<td>${user.updated_by}</td>
					`;
					tr.addEventListener( 'click', () => {
						this.fetchUserLogins( `${user.uid}` );
					});
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
				msg.textContent = 'User creation successfull';
				msg.style.color = '#065f46';
				this.fetchUsers();
				this.$( '#createUserForm' ).reset();
			}
			else {
				msg.textContent = data.error || 'Error creating new user';
				msg.style.color = '#b91c1c';
			}
		});
	}
	fetchUserLogins( uid ){
		fetch( `${this.app.baseURL}/api/backend`, {
			method: 'POST',
			body: new URLSearchParams( { action: "userlogins", uid: uid } )
		} )
		.then( response => response.json() )
		.then( data => { 
			if( data.success ){
				this.$( '#user-summary' ).classList.toggle( 'hidden' );
			}
			if( data.success && data.summary ){
				this.$( '#last-login-time' ).textContent = data.summary.last_login_time || '-';
				this.$( '#last-login-ip' ).textContent = data.summary.last_login_ip || '-';
				this.$( '#successfull-logins' ).textContent = data.summary.successfull_logins_total;
				this.$( '#failed-logins' ).textContent = data.summary.failed_logins_total;
			}
			if( data.success && Array.isArray( data.last_logins ) ){
				const tbody = this.$( '#loginLogTableBody' );
				tbody.innerHTML = '';
				data.last_logins.forEach( entry => {
					const row = document.createElement( 'tr' );
					row.innerHTML = `
						<td>${entry.success == 1 ? '✅' : '❌'}</td>
						<td>${entry.ip}</td>
						<td>${entry.user_agent}</td>
						<td>${entry.login_time}</td>
					`;
					tbody.appendChild(row);
				});
			}
		} );
	}
	init(){
		this.fetchUsers();
		this.$( '#createUserForm' ).addEventListener( 'submit', event => {
			event.preventDefault();
			this.createUser();
		} );
		this.$( '#close-summary' ).addEventListener( 'click', event => {
			this.$( '#user-summary' ).classList.toggle( 'hidden' );
		} );
	}
}