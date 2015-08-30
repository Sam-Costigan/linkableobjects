<?php
/**
 * The Linkable interface declares that any implementations of it should
 * have a public Link() function in order to be linkable and a LinkTitle()
 * function to give a relevant Title within the HTML Editor Field
 */
interface Linkable {

	/**
	 * The Link() function should return a URL which will navigate to the correct
	 * location to view the Object. A view function should be set up on the Controller
	 * which will be handling the display of this Object. For information on
	 * creating such functionality, see:
	 * http://www.ssbits.com/tutorials/2010/dataobjects-as-pages-part-1-keeping-it-simple/
	 *
	 * @return URL string
	 */
	public function Link();
	
	/**
	 * The LinkTitle() function should return the Title string that will be used
	 * within the link view. This should ideally be based on a db field to easily
	 * recognise the right Object to link to - however it is up to the Developer.
	 *
	 * @return Title string
	 */
	public function LinkTitle();

}