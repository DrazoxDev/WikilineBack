import React, { useState } from "react";

// https://developer.mozilla.org/en-US/docs/Learn/Tools_and_testing/Client-side_JavaScript_frameworks/React_todo_list_beginning

export default function Article(props) {
	const [isDeleting, setIsDeleting] = useState(false);
	const [isExpanded, setIsExpanded] = useState(false);
	const [isEditing, setIsEditing] = useState(false);
	const [titre, setTitre] = useState(props.article.titre);
	const [disponibilite, setDisponibilite] = useState(props.article.disponibilite);
	const [image, setImage] = useState(props.article.image);
	const [prix, setPrix] = useState(props.article.prix);


	if ( isEditing ) {
		// not loaded yet
		return (
			<tr className={
				isExpanded ? 'is-expanded' : ''
			} style={{
				opacity: isDeleting ? .3 : 1
			}} > 
				<td data-colname="Titre" className="column-primary ">
					<input onChange={event => setTitre(event.target.value)} type="text" name="titre" defaultValue={titre}/>
				</td>
				<td data-colname="Disponibilit&eacute;">
					<input onChange={event => setDisponibilite(event.target.value)} type="number" name="disponibilite" defaultValue={disponibilite}/>
				</td>
				<td data-colname="Image">
					<input onChange={event => setImage(event.target.value)} type="text" name="image" defaultValue={image}/>
				</td> 
				<td data-colname="Prix">
					<input onChange={event => setPrix(event.target.value)} type="number" name="prix" defaultValue={prix}/>
				</td>
				<td>
					<span onClick={() => {
						if (isEditing) {
							const data = props.article ;
							data.titre = titre ;
							data.disponibilite = disponibilite ;
							data.image = image ;
							data.prix = prix ;
							props.updateTask(data) ;
							setIsEditing(false) ;
						}
						else
							setIsEditing(true) ;
						}}>
						{ !isEditing ? <i className="fa fa-edit"></i> : <i className="fa fa-save"></i> }
					</span>
				</td>
				<td>
					<span onClick={() => {
						setIsDeleting(true) ;
						props.deleteTask(props.article.id) ;
						}}>
						<i type="submit" className="fa fa-trash"></i>
					</span>
				</td>
			</tr>
		);
	}
	return (
		<tr className={
			isExpanded ? 'is-expanded' : ''
		} style={{
			opacity: isDeleting ? .3 : 1
		}} >        
			<td data-colname="Titre" className="column-primary">
				<strong>{props.article.titre}</strong>
				<button type="button" className="toggle-row" onClick={(event) => {
					if (isExpanded)
						setIsExpanded(false) ;
					else
						setIsExpanded(true) ;
				}}><span className="screen-reader-text">Afficher plus de détails</span></button>
			</td>
			<td data-colname="Disponibilit&eacute;">{props.article.disponibilite}</td>
			<td data-colname="Image">{props.article.image}</td>
			<td data-colname="Prix">{props.article.prix}</td>
			<td>
				<span onClick={() => {
					if (isEditing)
						setIsEditing(false) ;
					else
						setIsEditing(true) ;
					}}>
					{ !isEditing ? <i className="fa fa-edit"></i> : <i className="fa fa-save"></i> }
				</span>
			</td>
			<td>
				<span onClick={() => {
					setIsDeleting(true) ;
					props.deleteTask(props.article.id) ;
					}}>
					<i className="fa fa-trash"></i>
				</span>
			</td>
		</tr>
	);
}