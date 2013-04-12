<?php
/*
Plugin Name: WP Posts Visualizer
Plugin URI: http://did2memo.net/
Description: You can visualize relationships between posts.
Version: 1.0.0
Author: did2
Author URI: http://did2memo.net/
License: GPL2
*/

add_action('admin_menu', 'wppv_add_admin_page');
add_action('wp_print_scripts', 'wppv_script_action');

$resource_name = 'postslink.json';
$groups_json = 'groups.json';
$wppv_plugin_url = trailingslashit('/wp-content/plugins/' . dirname(plugin_basename(__FILE__)));

function wppv_add_admin_page()
{
	add_management_page('WP Posts Visualizer', 'WP Posts Vis.', 8, __FILE__, 'wppv_create_admin_page');
}

function wppv_create_admin_page()
{
	global $wppv_plugin_url;
	global $resource_name, $groups_json;

	wppv_create_resource();

	?>

<style>

.link {
  stroke: #ccc;
}

.node text {
  pointer-events: none;
  font: 10px sans-serif;
}

</style>

<script type="text/javascript">
window.addEventListener("load", function() {


var resource = "<?php echo $wppv_plugin_url . $resource_name; ?>";
var group_resource = "<?php echo $wppv_plugin_url . $groups_json; ?>";

var width = 1960,
    height = 1300

var svg = d3.select("#canvas").append("svg")
    .attr("width", width)
    .attr("height", height);


// category

var groupForce = d3.layout.force()
    .gravity(.03)
    .linkDistance(150)
    .charge(-200)
    .size([width, height]);

var catNum_to_catNode = new Array();

d3.json(group_resource, function(catData) {
	groupForce
		.nodes(catData.nodes)
		.links(catData.links)
		.start();

	var groupLink = svg.selectAll("group.link")
		.data(catData.links)
		.enter().append("line")
		.attr("class", "group.link");

	var groupNode = svg.selectAll("group.node")
		.data(catData.nodes).enter()
		.append("g")
		.attr("class", "group.node")
		.attr("data-catnum", function (d, i) { return d.catnum })
		.call(groupForce.drag);

	groupForce.nodes().forEach(function(d, i) {
		catNum_to_catNode[i] = d;
	});

	var fill = d3.scale.category20();
	groupNode.append("circle")
		.attr("r", 0)
		.style("fill", function(d) { return fill(d.catnum); });

	groupNode.append("text")
		.attr("dx", 12)
		.attr("dy", ".35em")
		.text(function(d) { return d.name.substr(0, 10) + "..."; });

	groupForce.on("tick", function() {
		groupLink
			.attr("x1", function(d) { return d.source.x; })
			.attr("y1", function(d) { return d.source.y; })
			.attr("x2", function(d) { return d.target.x; })
			.attr("y2", function(d) { return d.target.y; });
	    
	    groupNode.attr("transform", function(d) { return "translate(" + d.x + "," + d.y + ")"; });
	});

});


// posts

var force = d3.layout.force()
    .gravity(.2)
    .linkDistance(30)
    .charge(-1000)
    .size([width, height]);

var nodeData = null;
var nodeToNodeData = null;
d3.json(resource, function(json) {
	// nodeData = json;
	// nodeData.forEach(function(d, i) {

	// });

force
	.nodes(json.nodes)
	.links(json.links)
	.start();

var
link = svg.selectAll(".link")
	.data(json.links)
	.enter().append("line")
	.attr("class", "link");

var
node = svg.selectAll(".node").data(json.nodes).enter()//;
//node
	.append("g")
	.attr("class", "node")
	.attr("data-catnum", function (d, i) { return d.catnum; })
	.call(force.drag);

var fill = d3.scale.category20();
node//.select("g")
		.append("circle")
			.attr("r", 5)
			.style("fill", function(d) { return fill(d.catnum); });

node//.select("g")
		.append("text")
			.attr("dx", 12)
			.attr("dy", ".35em")
			.text(function(d) { return d.name.substr(0, 10) + "..."; });

force.on("tick", function() {
	force.nodes().forEach(function(d, i) {
		var catNode = catNum_to_catNode[d.catnum];
		dx = catNode.x - d.x;
		dy = catNode.y - d.y;
		powx = dx * 1.5 * force.alpha();
		powy = dy * 1.5 * force.alpha();
		d.x += powx; //(dx > 30) ? powx : powx * force.alpha();
		d.y += powy; //(dy > 30) ? powy : powy * force.alpha();
	});

	link
		.attr("x1", function(d) { return d.source.x; })
		.attr("y1", function(d) { return d.source.y; })
		.attr("x2", function(d) { return d.target.x; })
		.attr("y2", function(d) { return d.target.y; });
    
    node.attr("transform", function(d) { return "translate(" + d.x + "," + d.y + ")"; });
  });

// force.nodes().forEach(function(d, i) {
// 	groupForce.links().push({})
// });

});



});
</script>


 <style type="text/css">

path.arc {
  cursor: move;
  fill: #fff;
}

.node {
  font-size: 10px;
}

.node:hover {
  fill: #1f77b4;
}

.link {
  fill: none;
  stroke: #1f77b4;
  stroke-opacity: .4;
  pointer-events: none;
}

.link.source, .link.target {
  stroke-opacity: 1;
  stroke-width: 2px;
}

.node.target {
  fill: #d62728 !important;
}

.link.source {
  stroke: #d62728;
}

.node.source {
  fill: #2ca02c;
}

.link.target {
  stroke: #2ca02c;
}

    </style>

<script type="text/javascript">

var w = 1280,
    h = 800,
    rx = w / 2,
    ry = h / 2,
    m0,
    rotate = 0;

var splines = [];

var cluster = d3.layout.cluster()
    .size([360, ry - 120])
    .sort(function(a, b) { return d3.ascending(a.key, b.key); });

var bundle = d3.layout.bundle();

var line = d3.svg.line.radial()
    .interpolate("bundle")
    .tension(.85)
    .radius(function(d) { return d.y; })
    .angle(function(d) { return d.x / 180 * Math.PI; });

// Chrome 15 bug: <http://code.google.com/p/chromium/issues/detail?id=98951>
var div = d3.select("#canvas2").insert("div", "h2")
    .style("top", "-80px")
    .style("left", "-160px")
    .style("width", w + "px")
    .style("height", w + "px")
    .style("position", "absolute");

var svg = div.append("svg:svg")
    .attr("width", w)
    .attr("height", w)
  .append("svg:g")
    .attr("transform", "translate(" + rx + "," + ry + ")");

svg.append("svg:path")
    .attr("class", "arc")
    .attr("d", d3.svg.arc().outerRadius(ry - 120).innerRadius(0).startAngle(0).endAngle(2 * Math.PI))
    .on("mousedown", mousedown);

d3.json( "<?php echo $wppv_plugin_url . 'flare-imports.json' ?>" , function(classes) {
  var nodes = cluster.nodes(packages.root(classes)),
      links = packages.imports(nodes),
      splines = bundle(links);

  var path = svg.selectAll("path.link")
      .data(links)
    .enter().append("svg:path")
      .attr("class", function(d) { return "link source-" + d.source.key + " target-" + d.target.key; })
      .attr("d", function(d, i) { return line(splines[i]); });

  svg.selectAll("g.node")
      .data(nodes.filter(function(n) { return !n.children; }))
    .enter().append("svg:g")
      .attr("class", "node")
      .attr("id", function(d) { return "node-" + d.key; })
      .attr("transform", function(d) { return "rotate(" + (d.x - 90) + ")translate(" + d.y + ")"; })
    .append("svg:text")
      .attr("dx", function(d) { return d.x < 180 ? 8 : -8; })
      .attr("dy", ".31em")
      .attr("text-anchor", function(d) { return d.x < 180 ? "start" : "end"; })
      .attr("transform", function(d) { return d.x < 180 ? null : "rotate(180)"; })
      .text(function(d) { return d.key; })
      .on("mouseover", mouseover)
      .on("mouseout", mouseout);

  d3.select("input[type=range]").on("change", function() {
    line.tension(this.value / 100);
    path.attr("d", function(d, i) { return line(splines[i]); });
  });
});

d3.select(window)
    .on("mousemove", mousemove)
    .on("mouseup", mouseup);

function mouse(e) {
  return [e.pageX - rx, e.pageY - ry];
}

function mousedown() {
  m0 = mouse(d3.event);
  d3.event.preventDefault();
}

function mousemove() {
  if (m0) {
    var m1 = mouse(d3.event),
        dm = Math.atan2(cross(m0, m1), dot(m0, m1)) * 180 / Math.PI;
    div.style("-webkit-transform", "translate3d(0," + (ry - rx) + "px,0)rotate3d(0,0,0," + dm + "deg)translate3d(0," + (rx - ry) + "px,0)");
  }
}

function mouseup() {
  if (m0) {
    var m1 = mouse(d3.event),
        dm = Math.atan2(cross(m0, m1), dot(m0, m1)) * 180 / Math.PI;

    rotate += dm;
    if (rotate > 360) rotate -= 360;
    else if (rotate < 0) rotate += 360;
    m0 = null;

    div.style("-webkit-transform", "rotate3d(0,0,0,0deg)");

    svg
        .attr("transform", "translate(" + rx + "," + ry + ")rotate(" + rotate + ")")
      .selectAll("g.node text")
        .attr("dx", function(d) { return (d.x + rotate) % 360 < 180 ? 8 : -8; })
        .attr("text-anchor", function(d) { return (d.x + rotate) % 360 < 180 ? "start" : "end"; })
        .attr("transform", function(d) { return (d.x + rotate) % 360 < 180 ? null : "rotate(180)"; });
  }
}

function mouseover(d) {
  svg.selectAll("path.link.target-" + d.key)
      .classed("target", true)
      .each(updateNodes("source", true));

  svg.selectAll("path.link.source-" + d.key)
      .classed("source", true)
      .each(updateNodes("target", true));
}

function mouseout(d) {
  svg.selectAll("path.link.source-" + d.key)
      .classed("source", false)
      .each(updateNodes("target", false));

  svg.selectAll("path.link.target-" + d.key)
      .classed("target", false)
      .each(updateNodes("source", false));
}

function updateNodes(name, value) {
  return function(d) {
    if (value) this.parentNode.appendChild(this);
    svg.select("#node-" + d[name].key).classed(name, value);
  };
}

function cross(a, b) {
  return a[0] * b[1] - a[1] * b[0];
}

function dot(a, b) {
  return a[0] * b[0] + a[1] * b[1];
}

</script>


<h2>WP Posts Visualizer</h2>

<div id="canvas"></div>
<div id="canvas2">
	<h2>
      Flare imports<br>
      hierarchical edge bundling
    </h2>
    <div style="position:absolute;bottom:0;font-size:18px;">tension: <input style="position:relative;top:3px;" type="range" min="0" max="100" value="85"></div>
</div>


<?php
}

function wppv_create_resource() {
	global $wppv_plugin_url;
	global $resource_name;
	global $groups_json;

	$filename = ABSPATH . $wppv_plugin_url . $resource_name;
	$catfilename = ABSPATH . $wppv_plugin_url . $groups_json;

	$data = array();
	$nodes = array();
	$links = array();

	$cat_nodes = array();
	$url_to_nodenum = array();
	$catid_to_catnum = array();

	$nodenum_i = 0;
	$catnum_i = 0;

	if (!file_exists($filename)) {

		query_posts('posts_per_page=-1&post_type=post&post_status=publish');

		// loop to construct nodes
		if( have_posts() ) : while ( have_posts() ) : the_post();
			$name = get_the_title();

			$cat = get_the_category(); $cat = $cat[0];
			$catid = $cat->cat_ID;
			$catname = $cat->name;
			if( !isset( $catid_to_catnum[$catid]) ) {
				$catid_to_catnum[$catid] = $catnum_i++;
				$catnodes[] = array( 'name'=>$catname , 'catnum'=>$catid_to_catnum[$catid] , 'value'=>$cat->count);
			}
			$catnum = $catid_to_catnum[$catid];

			$url = get_permalink($post->ID);
			$url_to_nodenum[$url] = $nodenum_i++;

			$nodes[] = array( 'name'=>$name , 'catnum'=>$catnum , 'url'=>$url );
		endwhile; endif;

		// loop to construct links
		if( have_posts() ) : while ( have_posts() ) : the_post();
			$content = get_the_content();
			$url = get_permalink($post->ID);
			
			$linkpattern = "/<a[^>]+href=[\"']?([-_.!~*'()a-zA-Z0-9;\/?:\@&=+\$,%#]+)[\"']?[^>]*>(.*?)<\/a>/ims";
			preg_match_all( $linkpattern , $content , $matches );
			$matches[0] = ""; // full matched text array ($matches[1] is a captured sub-pattern matched text array)
			foreach( $matches[1] as $val ) {
				if( isset( $url_to_nodenum[$val] ) ) {
					$source = $url_to_nodenum[$url];
					$target = $url_to_nodenum[$val];
					$value = 1;

					$links[] = array( 'source'=>$source , 'target'=>$target , 'value'=>$value );
				}
			}
		endwhile; endif;

		wp_reset_query();

		$data = array( 'nodes'=>$nodes , 'links'=>$links );
		file_put_contents( $filename , json_encode( $data ) );

		$data = array( 'nodes'=>$catnodes , 'links'=>array() );
		file_put_contents( $catfilename , json_encode( $data ) );
	}
}

function wppv_script_action()
{
	global $wppv_plugin_url;

	if ('wp-posts-visualizer/wp-posts-visualizer.php' != $_GET['page']) {
        return '';
    }

    wp_enqueue_script( 'd3.js' , 'http://d3js.org/d3.v3.min.js' );
    /* wp_enqueue_script( 'd3.layout.js' , $wppv_plugin_url . 'd3.layout.js' , array( 'd3.js' ) ); */
    wp_enqueue_script( 'packages.js' , $wppv_plugin_url . 'packages.js', array( 'd3.js' ) );
}

?>