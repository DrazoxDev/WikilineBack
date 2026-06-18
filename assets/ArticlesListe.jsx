import React, { Component } from "react";
import Article from './Article.jsx';


// https://developer.mozilla.org/en-US/docs/Learn/Tools_and_testing/Client-side_JavaScript_frameworks/React_todo_list_beginning

export default class ArticlesListe extends Component {
	
    constructor(props) {
        super(props);
        this.state = {
            loaded: false,
            articles: null,
			error: ''
        };

        this.abortController = new AbortController(); 
		this.apiToFetchBase = import.meta.env.VITE_APP_API_URL || (window.location.hostname === "localhost" || window.location.hostname === "127.0.0.1" ? "http://localhost:8000" : ".");
    }
	
    // Invoked immediately after a component is mounted (inserted into the DOM tree)
    componentDidMount() {
        this.fetchApiToEntries() ;
    }
	
    componentWillUnmount() {
        this.abortController.abort();
    }
	
    fetchApiToEntries = async () => {
		let apiToFetch = this.apiToFetchBase + '/wp-json/wc/v3/products' ;
		try {
			let response = await fetch(apiToFetch, {
                method: "GET",
                signal: this.abortController.signal
            });
			if(response.status === 200) {
				let data = await response.json(); // data is the result of parsing the response body text as JSON
				this.setState({
					loaded: true,
					articles: data
				})
			} else {
				throw new Error("Error code " + response.status);
			}
		} catch (error) {
			if (error.name === 'AbortError') return;
			this.setState({
				loaded: true,
				error: error.message
			});
			console.log(error);
		}
    }

	fetchApiToEntriesDelete = async (id) => { 
		let apiToFetch = this.apiToFetchBase + '/wp-json/wc/v3/products/' + id ; // apiToFetch is the path to the resource you want to fetch
		try {
			let response = await fetch(apiToFetch, {
                method: "DELETE",
                signal: this.abortController.signal
            });
			//let text = await response.text();
			if(response.status === 204) {
				const data = this.state.articles.filter((article) => article.id !== id);
				this.setState({
					loaded: true,
					articles: data,
					error: ''
				})
			} else {
				throw new Error("Error code " + response.status);
			}
		} catch (error) {
			if (error.name === 'AbortError') return;
			this.setState({
				loaded: true,
				error: error.message
			});
			console.log(error);
		}
    }


    fetchApiToEntriesUpdate = async (articleNotUpdated) => {
        let id = articleNotUpdated.id ;
		let apiToFetch = this.apiToFetchBase + '/wp-json/wc/v3/products/' + id ; // apiToFetch is the path to the resource you want to fetch
		try {	
			let response = await fetch(apiToFetch, { 
				method: "PUT", 
				headers: {"Content-type": "application/json; charset=UTF-8"},
				body: JSON.stringify(articleNotUpdated),
				signal: this.abortController.signal
			});
			if(response.status === 200) {
				let articleUpdated = await response.json(); // data is the result of parsing the response body text as JSON
				const data = this.state.articles.map((article) => {
					if (article.id === articleUpdated.id) {
						const updatedArticle = {
						  ...articleUpdated,
						};
						return updatedArticle;
					  }
					  return article;
				});
				this.setState({
					loaded: true,
					articles: data,
					error: ''
				})
			} else {
				throw new Error("Error code " + response.status);
			}
		} catch (error) {
			if (error.name === 'AbortError') return;
			this.setState({
				loaded: true,
				error: error.message
			});
			console.log(error);
			this.fetchApiToEntries() ;
		}
    }

    render() {
        if ( !this.state.loaded ) {
            // not loaded yet
            return <div>Chargement...</div>;
        }
		return (
				<div>
					{this.state.articles && <table className="wp-list-table widefat">
						<thead>
							<tr>
								<th className="column-primary">Titre</th>
								<th>Disponibilit&eacute;</th>
								<th>Image</th>
								<th>prix</th>
								<th>&nbsp;</th>
								<th>&nbsp;</th>
							</tr>
						</thead>
						<tbody>
							{this.state.articles.map((article, index) => (
							<Article 
							id={article.id} 
							article={article} 
							key={article.id}
							deleteTask={this.fetchApiToEntriesDelete}
							updateTask={this.fetchApiToEntriesUpdate}
							/>
							))}
						</tbody>
						<tfoot>
						</tfoot>
					</table>}
					{this.state.error && <div style={{ color: 'red' }}>{this.state.error}</div>}
				</div>
		);
    }
}