<?php
/*
 Plugin Name: Random Navigation
 Description: navigation widget which provides navigation menu with many features. Absolutely nothing random :)
 Version: 0.6
 Author: Frederik Reiß
 Author URI: http://random-internet-node.de
 License: GPLv2
 */

/* Add our function to the widgets_init hook. */
add_action( 'widgets_init', array('Random_Nav', 'register_widget') );


class Random_Nav extends WP_Widget{
	private $this_page_pos=array();
	private $this_page=0;
	private $this_page_type='';
	private $order=array(
		'page' => 'menu_order',
		'category' => 'title',
		'tag' => 'title'
	);
	private $blogID=0;
	private $blog_title='Blog';
	private $excludeIDs=array();


	public function Random_Nav() {
		$widget_ops = array( 'classname' => 'widget-random-nav', 'description' => 'tree navigation widget with many features' );
		$control_ops = array( 'width' => 300, 'height' => 350, 'id_base' => 'random-nav' );
		$this->WP_Widget( 'random-nav', 'Random nvigation', $widget_ops, $control_ops );
	}

	static function register_widget() {
		register_widget( __CLASS__ );
	}

	public function widget($args, $instance) {
		global $wp_query;
		extract( $args );

		$title=$instance['title'];

		$always_expand_menu=(isset($instance['always_expand_menu']) && $instance['always_expand_menu'] == 'on')?true:false;
		$use_first_visible_as_title=(isset($instance['use_first_visible_as_title']) && $instance['use_first_visible_as_title'] == 'on')?true:false;
		$show_path_only=(isset($instance['show_path_only']) && $instance['show_path_only'] == 'on')?true:false;
		$start_level=(int)$instance['start_level'];
		$end_level=(int)$instance['end_level'];
		$parent_id=$instance['parent_id'];
		$show_blog=(isset($instance['show_blog']) && $instance['show_blog'] == 'on')?true:false;
		$show_below_blog=$instance['show_below_blog'];
		$blog_parent_id=$instance['blog_parent_id'];
		$blog_menu_order=$instance['blog_menu_order'];



		if (trim($instance['blog_title']) != ''){
			$this->blog_title=$instance['blog_title'];
		}

		if (get_option('page_for_posts') !== false && get_option('page_for_posts') > 0){
			$this->blogID=get_option('page_for_posts');
		}else{
			$first_blog_post=get_posts('numberposts=1');
			$this->blogID=$first_blog_post[0]->ID;
		}

		if (trim($instance['exclude_ids']) != ''){
			$exclude_pages_raw=explode(',', $instance['exclude_ids']);
			foreach($exclude_pages_raw as $epr){
				if (ctype_digit((string)trim($epr))){
					$this->excludeIDs[]=trim($epr);
				}
			}
		}



		if(is_page()){
			$this->this_page=$wp_query->post->ID;
			$this->this_page_type='page';
		}else if(is_category() && $show_below_blog=='category' && $show_blog){
			$this->this_page=$wp_query->query_vars['cat'];
			$this->this_page_type='category';
		}else if(is_tag() && $show_below_blog=='tag' && $show_blog){
			$this->this_page=$wp_query->query_vars['tag_id'];
			$this->this_page_type='tag';
		}else if(is_home() || is_single() || is_archive() || is_404() || is_search() || (((is_category() && $show_below_blog!='category') || (is_tag() && $show_below_blog!='tag')) && $show_blog)){
			$this->this_page=$this->blogID;
			$this->this_page_type='page';
		}else{
			echo 'unknown';
		}

		$tree=$this->get_tree('page', $parent_id, array(), $show_blog, $show_below_blog, $blog_parent_id, $blog_menu_order);

		echo $before_widget;
		if ($use_first_visible_as_title && $start_level > 0){
			if ($this->this_page_pos[($start_level-1)]['id'] == $this->blogID){
				$title=$this->blog_title;
			}else if($parent_id == 0){
				$p=get_page($this->this_page_pos[($start_level-1)]['id']);
				$title=$p->post_title;
			}else if ($parent_id !=0){
				$p=get_page($parent_id);
				$title=$p->post_title;
			}
		}

		if ($title){
			echo $before_title.apply_filters('widget_title', $title ).$after_title;
		}

		$this->list_tree($tree, 0, $start_level, $end_level, $always_expand_menu, $show_path_only);
		echo $after_widget;
	}





	private function get_tree($type, $parent_id, $path, $show_blog, $show_below_blog, $blog_parent_id, $blog_menu_order){
		$pages=array();
		$tree=array();
		if($parent_id == -1){
			return $pages;
		}

		if ($type=='page'){
			$pages=get_pages(array('parent'=>$parent_id, 'hierarchical' => false));
			if ($show_blog == true && $blog_parent_id == $parent_id){

				$path_new=$path;
				$path_new[]=array('id'=>$this->blogID, 'type'=>$type);

				if ($this->this_page == $this->blogID){
					$this->this_page_pos=$path_new;
				}

				$blog=new stdClass;
				$blog->ID=$this->blogID;
				$blog->type=$type;
				$blog->title=$this->blog_title;
				$blog->menu_order=$blog_menu_order;
				$blog->uri='/';
				$blog->path=$path_new;
				$blog->childs=$this->get_tree($show_below_blog, 0, $path_new, false, $show_below_blog, $blog_parent_id, $blog_menu_order);

				$tree[]=$blog;
			}
		}else if($type=='category'){
			$pages=get_categories(array('parent' => $parent_id, 'hierarchical' => false));
		}else if($type=='tag'){
			$pages=get_tags(array('parent' => $parent_id, 'hierarchical' => false));
		}


		if (count($pages) > 0){
			foreach($pages as &$page){

				if ($type=='page' && in_array($page->ID, $this->excludeIDs)){
					continue;
				}

				if ($type=='page'){
					$entry=new stdClass;
					$entry->ID= $page->ID;
					$entry->type=$type;
					$entry->title=$page->post_title;
					$entry->menu_order=$page->menu_order;
					$entry->uri='/'.get_page_uri( $page->ID );

				}else if($type=='category'){
					$entry=new stdClass;
					$entry->ID= $page->cat_ID;
					$entry->type=$type;
					$entry->title=$page->name;
					$entry->menu_order=0;
					$entry->uri=get_category_link($page->cat_ID);

				}else if($type=='tag'){
					$entry=new stdClass;
					$entry->ID= $page->term_id;
					$entry->type=$type;
					$entry->title=$page->name;
					$entry->menu_order=0;
					$entry->uri=get_tag_link($page->term_id);
				}

				$path_new2=$path;
				$path_new2[]=array('id'=>$entry->ID, 'type'=>$entry->type);
				$entry->path=$path_new2;
				if ($this->this_page == $entry->ID && $this->this_page_type == $entry->type){
					$this->this_page_pos=$path_new2;
				}

				$entry->childs=$this->get_tree($type, $entry->ID, $path_new2, $show_blog, $show_below_blog, $blog_parent_id, $blog_menu_order);
				$tree[]=$entry;

			}
			uasort($tree, array($this, 'sort_array'));
		}

		return $tree;
	}



	private function list_tree($tree, $level, $start_level, $end_level, $always_expand_menu, $show_path_only){
		if ($end_level != -1 && $end_level <= $level){
			return;
		}

		$listLevel=true;
		if ($level < $start_level){
			$listLevel=false;
		}

		if ($listLevel){
			echo '<ul class="random-nav-ul-level-'.$level.'">';
		}


		foreach($tree as $page){
			$classes=array();
			if ($this->this_page_pos[$level]['id'] == $page->ID && $this->this_page_pos[$level]['type'] == $page->type){
				$classes[]='random-nav-in-path';
				if ($this->this_page == $page->ID){
					$classes[]='random-nav-active-page';
				}
			}else{
				if ($show_path_only){
					continue;
				}
			}
			if (count($page->childs) > 0){
				$classes[]='random-nav-has-children';
			}

			$classes[]='random-nav-entry-id-'.(int)$page->ID;
			$classes[]='random-nav-type-'.$page->type;

			$classes[]='random-nav-level-'.$level;

			if (count($classes) > 0){
				$classes=' class="'.implode(' ', $classes).'"';
			}else{
				$classes="";
			}

			if ($listLevel){
				echo '<li'.$classes.'><span'.$classes.'><a href="'.esc_attr($page->uri).'">'.esc_html($page->title).'</a></span>';
			}

			if($always_expand_menu || (count($page->childs) > 0 && $this->this_page_pos[$level]['id'] == $page->ID && $this->this_page_pos[$level]['type'] == $page->type)){
				if ($this->this_page == $page->ID && $end_level > ($level+2)){
					$this->list_tree($page->childs, $level+1, $start_level, $level+2, $always_expand_menu, $show_path_only);
				}else{
					$this->list_tree($page->childs, $level+1, $start_level, $end_level, $always_expand_menu, $show_path_only);
				}
			}

			if ($listLevel){
				echo '</li>';
			}
		}

		if ($listLevel){
			echo '</ul>';
		}


	}



	private function sort_array($a, $b) {

		$aprop=$this->order[$a->type];
		$bprop=$this->order[$b->type];

		if ($a->$aprop == $b->$bprop) {
			return 0;
		}

		return ($a->$aprop < $b->$bprop) ? -1 : 1;
	}


	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		$instance['title']=strip_tags( $new_instance['title'] );
		$instance['always_expand_menu']=$new_instance['always_expand_menu'];
		$instance['use_first_visible_as_title']=$new_instance['use_first_visible_as_title'];
		$instance['show_path_only']=$new_instance['show_path_only'];
		$instance['start_level']=(int)$new_instance['start_level'];
		$instance['end_level']=(int)$new_instance['end_level'];
		$instance['parent_id']=(int)$new_instance['parent_id'];
		$instance['exclude_ids']=$new_instance['exclude_ids'];
		$instance['show_blog']=$new_instance['show_blog'];
		$instance['blog_title']=strip_tags($new_instance['blog_title']);
		$instance['show_below_blog']=strip_tags( $new_instance['show_below_blog'] );
		$instance['blog_parent_id']=(int)$new_instance['blog_parent_id'];
		$instance['blog_menu_order']=(int)$new_instance['blog_menu_order'];

		return $instance;
	}



	public function form( $instance ) {

		/* Set up some default widget settings. */
		$defaults = array( 'title' => 'Navigation',
				 'use_first_visible_as_title' => false,
				 'always_expand_menu' => false,
				 'show_path_only' => false,
				 'start_level' => 0,
				 'end_level' => -1,
				 'parent_id' => 0,
				 'exclude_ids' => '',
				 'show_blog' => true,
				 'blog_title' => 'Blog',
				 'show_below_blog' => 'category',
				 'blog_parent_id' => 0,
				 'blog_menu_order' => 5

		);
		$instance = wp_parse_args( (array) $instance, $defaults );


		?>

<p><label for="<?php echo $this->get_field_id( 'title' ); ?>">Widget
title:</label> <input id="<?php echo $this->get_field_id( 'title' ); ?>"
name="<?php echo $this->get_field_name( 'title' ); ?>"
value="<?php echo $instance['title']; ?>" style="width: 100%;" /></p>

<p><input class="checkbox" type="checkbox"
<?php checked( $instance['use_first_visible_as_title'], 'on' ); ?>
id="<?php echo $this->get_field_id( 'use_first_visible_as_title' ); ?>"
name="<?php echo $this->get_field_name( 'use_first_visible_as_title' ); ?>" />
<label
	for="<?php echo $this->get_field_id( 'use_first_visible_as_title' ); ?>">Use
page directly above start level page for widget title</label></p>

<p><label for="<?php echo $this->get_field_id( 'parent_id' ); ?>">Menu
root:</label><br>
<? wp_dropdown_pages("name=".$this->get_field_name( 'parent_id' )."&selected=".$instance['parent_id']."&show_option_none=(Toplevel)"); ?>
</p>

<table style="margin-bottom: 1em;">
<tr>
	<td><label for="<?php echo $this->get_field_id( 'start_level' ); ?>">Start
level<br>
(0 for Toplevel):</label></td>
<td><label for="<?php echo $this->get_field_id( 'end_level' ); ?>">End
	level<br>
	(-1 for infinite):</label></td>
</tr>
<tr>
	<td><input id="<?php echo $this->get_field_id( 'start_level' ); ?>"
	name="<?php echo $this->get_field_name( 'start_level' ); ?>"
	value="<?php echo $instance['start_level']; ?>" /></td>
<td><input id="<?php echo $this->get_field_id( 'end_level' ); ?>"
	name="<?php echo $this->get_field_name( 'end_level' ); ?>"
	value="<?php echo $instance['end_level']; ?>" /></td>
	</tr>
</table>

<p><input class="checkbox" type="checkbox"
<?php checked( $instance['always_expand_menu'], 'on' ); ?>
id="<?php echo $this->get_field_id( 'always_expand_menu' ); ?>"
name="<?php echo $this->get_field_name( 'always_expand_menu' ); ?>" />
<label for="<?php echo $this->get_field_id( 'always_expand_menu' ); ?>">Always
expand full menu</label></p>

<p><input class="checkbox" type="checkbox"
<?php checked( $instance['show_path_only'], 'on' ); ?>
id="<?php echo $this->get_field_id( 'show_path_only' ); ?>"
name="<?php echo $this->get_field_name( 'show_path_only' ); ?>" /> <label
for="<?php echo $this->get_field_id( 'show_path_only' ); ?>">Show page
path only</label></p>

<p><!-- select multiple would be nice, but this needs http://core.trac.wordpress.org/ticket/15523 to be fixed first -->
<label for="<?php echo $this->get_field_id( 'exclude_ids' ); ?>">Exclude
Page IDs (Comma seperated list):</label> <input
	id="<?php echo $this->get_field_id( 'exclude_ids' ); ?>"
name="<?php echo $this->get_field_name( 'exclude_ids' ); ?>"
value="<?php echo $instance['exclude_ids']; ?>" style="width: 100%;" />
</p>


<fieldset><label>Blog options</label>

<p><input class="checkbox" type="checkbox"
<?php checked( $instance['show_blog'], 'on' ); ?>
id="<?php echo $this->get_field_id( 'show_blog' ); ?>"
name="<?php echo $this->get_field_name( 'show_blog' ); ?>" /> <label
for="<?php echo $this->get_field_id( 'show_blog' ); ?>">Show blog as
extra page</label></p>

<p><label for="<?php echo $this->get_field_id( 'blog_title' ); ?>">Blog
title:</label> <input
	id="<?php echo $this->get_field_id( 'blog_title' ); ?>"
name="<?php echo $this->get_field_name( 'blog_title' ); ?>"
value="<?php echo $instance['blog_title']; ?>" style="width: 100%;" />
</p>

<p><label for="<?php echo $this->get_field_id( 'blog_parent_id' ); ?>">Show
blog below this page:</label><br>
<? wp_dropdown_pages("name=".$this->get_field_name( 'blog_parent_id' )."&selected=".$instance['blog_parent_id']."&show_option_none=(Toplevel)"); ?>
</p>

<p><label for="<?php echo $this->get_field_id( 'blog_menu_order' ); ?>">Blog
menu order:</label> <input
	id="<?php echo $this->get_field_id( 'blog_menu_order' ); ?>"
name="<?php echo $this->get_field_name( 'blog_menu_order' ); ?>"
value="<?php echo $instance['blog_menu_order']; ?>"
style="width: 100%;" /></p>


<p><label for="<?php echo $this->get_field_id( 'show_below_blog' ); ?>">What
to show below blog page:</label> <select
	id="<?php echo $this->get_field_id( 'show_below_blog' ); ?>"
name="<?php echo $this->get_field_name( 'show_below_blog' ); ?>"
class="widefat" style="width: 100%;">
<option value="nothing"
<?php if ( 'nothing' == $instance['show_below_blog'] ) echo 'selected="selected"'; ?>>Nothing</option>
<option value="category"
<?php if ( 'category' == $instance['show_below_blog'] ) echo 'selected="selected"'; ?>>Categories</option>
<option value="tag"
<?php if ( 'tag' == $instance['show_below_blog'] ) echo 'selected="selected"'; ?>>Tags</option>
</select></p>

</fieldset>


<?php
	}
}

?>
