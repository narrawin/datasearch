var units = "Datasets";

var margin = {top: 10, right: 10, bottom: 10, left: 10},
    width = 1000 - margin.left - margin.right,
    height = 1000 - margin.top - margin.bottom;

var formatNumber = d3.format(",.0f"),    // zero decimal places
    format = function(d) { return formatNumber(d) + " " + units; },
    color = d3.scale.category20();

// append the svg canvas to the page
var svg = d3.select("#chart").append("svg")
    .attr("width", width + margin.left + margin.right)
    .attr("height", height + margin.top + margin.bottom)
  .append("g")
    .attr("transform", 
          "translate(" + margin.left + "," + margin.top + ")");

// Set the sankey diagram properties
var sankey = d3.sankey()
    .nodeWidth(36)
    .nodePadding(12)
    .size([width, height]);

var path = sankey.link();

loadData();

function loadData() {

  svg.selectAll('*').remove();  //  clear existig graph
  
  var graphTitle = "Catalogued datasets in relation to " + document.getElementById("dataFile").value.slice(0, -4);
  var dataFile = "csv/" + document.getElementById("dataFile").value;
  if (!dataFile) {
    alert("please select a data file!");
  }
  
  //console.log(dataFile);
  document.getElementById("chartTitle").innerHTML = "loaded file: " + dataFile;
  document.getElementById("graph-heading").innerHTML = graphTitle;

  d3.csv(dataFile, function(error, data) {

    //set up graph in same style as original example but empty
    graph = {"nodes" : [], "links" : []};

      data.forEach(function (d) {
        graph.nodes.push({ "name": d.source });
        graph.nodes.push({ "name": d.target });
        graph.links.push({ "source": d.source,
                           "target": d.target,
                           "value": +d.value });
       });

       // return only the distinct / unique nodes
       graph.nodes = d3.keys(d3.nest()
         .key(function (d) { return d.name; })
         .map(graph.nodes));

       // loop through each link replacing the text with its index from node
       graph.links.forEach(function (d, i) {
         graph.links[i].source = graph.nodes.indexOf(graph.links[i].source);
         graph.links[i].target = graph.nodes.indexOf(graph.links[i].target);
       });

       //now loop through each nodes to make nodes an array of objects
       graph.nodes.forEach(function (d, i) {
         graph.nodes[i] = { "name": d };
       });

    sankey
      .nodes(graph.nodes)
      .links(graph.links)
      .layout(32);
  
    var link = svg.append("g").selectAll(".link") // add in the links
        .data(graph.links)
      .enter().append("path")
        .attr("class", "link")
        .attr("d", path)
        .style("stroke-width", function(d) { return Math.max(1, d.dy); })
        .sort(function(a, b) { return b.dy - a.dy; });
  
    link.append("title")  // add the link titles
          .text(function(d) {
          return d.source.name + " -> " + 
                  d.target.name + "\n" + format(d.value); });

    var node = svg.append("g").selectAll(".node") // add nodes
        .data(graph.nodes)
      .enter().append("g")
        .attr("class", "node")
        .attr("transform", function(d) { 
        return "translate(" + d.x + "," + d.y + ")"; })
      .call(d3.behavior.drag()
        .origin(function(d) { return d; })
        .on("dragstart", function() { 
        this.parentNode.appendChild(this); })
        .on("drag", dragmove));

    node.append("rect") // add the rectangles for nodes
        .attr("height", function(d) { return d.dy; })
        .attr("width", sankey.nodeWidth())
        .style("fill", function(d) { 
        return d.color = color(d.name.replace(/ .*/, "")); })
        .style("stroke", function(d) { 
        return d3.rgb(d.color).darker(2); })
      .append("title")
        .text(function(d) { 
        return d.name + "\n" + format(d.value); });

    node.append("text")  // add node titles
        .attr("x", -6)
        .attr("y", function(d) { return d.dy / 2; })
        .attr("dy", ".35em")
        .attr("text-anchor", "end")
        .attr("transform", null)
        .text(function(d) { return (d.name + ' (' + d.value + ')'); })
      .filter(function(d) { return d.x < width / 2; })
        .attr("x", 6 + sankey.nodeWidth())
        .attr("text-anchor", "start");

    // svg.append("text")  // add title
    //   .attr("class", "title")
    //   .attr("x", width/2)
    //   .attr("y", 10 )
    //   .attr("text-anchor", "middle")
    //   .text("Catalogued datasets in relation to their characteristics");

    function dragmove(d) {  // function for moving nodes
      d3.select(this).attr("transform", 
          "translate(" + d.x + "," + (
                  d.y = Math.max(0, Math.min(height - d.dy, d3.event.y))
              ) + ")");
      sankey.relayout();
      link.attr("d", path);
    }
  });

}
