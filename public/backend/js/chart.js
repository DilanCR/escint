

var options = {
  series: [{
  name: 'Pending',
  color: "#5A5278",
  data: [44, 55, 41, 67, 22, 43]
}, {
  name: 'Completed',
  color: "#6F6593",
  data: [13, 23, 20, 8, 13, 27]
}, {
  name: 'Canceled',
  color: "#8075AA",
  data: [11, 17, 15, 15, 21, 14]
}, {
  name: 'All',
  color: "#A192D9",
  data: [21, 7, 25, 13, 22, 8]
}],
  chart: {
  type: 'bar',
  height: 350,
  stacked: true,
  toolbar: {
    show: false
  },
  zoom: {
    enabled: true
  }
},
responsive: [{
  breakpoint: 480,
  options: {
    legend: {
      position: 'bottom',
      offsetX: -10,
      offsetY: 0
    }
  }
}],
plotOptions: {
  bar: {
    horizontal: true,
    borderRadius: 10
  },
},
xaxis: {
  type: 'datetime',
  categories: ['01/01/2011 GMT', '01/02/2011 GMT', '01/03/2011 GMT', '01/04/2011 GMT',
    '01/05/2011 GMT', '01/06/2011 GMT'
  ],
},
legend: {
  position: 'bottom',
  offsetX: 40
},
fill: {
  opacity: 1
}
};



// pie-chart
$(function() {
  $('#chart6').easyPieChart({
      size: 80,
      barColor: '#f05050',
      scaleColor: false,
      lineWidth: 5,
      trackColor: '#f050505a',
      lineCap: 'circle',
      animate: 3000
  });
});

$(function() {
  $('#chart7').easyPieChart({
      size: 80,
      barColor: '#10c469',
      scaleColor: false,
      lineWidth: 5,
      trackColor: '#10c4695a',
      lineCap: 'circle',
      animate: 3000
  });
});

$(function() {
  $('#chart8').easyPieChart({
      size: 80,
      barColor: '#ffbd4a',
      scaleColor: false,
      lineWidth: 5,
      trackColor: '#ffbd4a5a',
      lineCap: 'circle',
      animate: 3000
  });
});

$(function() {
  $('#chart9').easyPieChart({
      size: 80,
      barColor: '#ff8acc',
      scaleColor: false,
      lineWidth: 5,
      trackColor: '#ff8acc5a',
      lineCap: 'circle',
      animate: 3000
  });
});

$(function() {
  $('#chart10').easyPieChart({
      size: 80,
      barColor: '#7367f0',
      scaleColor: false,
      lineWidth: 5,
      trackColor: '#7367f05a',
      lineCap: 'circle',
      animate: 3000
  });
});

$(function() {
  $('#chart11').easyPieChart({
      size: 80,
      barColor: '#1e9ff2',
      scaleColor: false,
      lineWidth: 5,
      trackColor: '#1e9ff25a',
      lineCap: 'circle',
      animate: 3000
  });
});

$(function() {
  $('#chart12').easyPieChart({
      size: 80,
      barColor: '#5a5278',
      scaleColor: false,
      lineWidth: 5,
      trackColor: '#5a52785a',
      lineCap: 'circle',
      animate: 3000
  });
});

$(function() {
  $('#chart13').easyPieChart({
      size: 80,
      barColor: '#ADDDD0',
      scaleColor: false,
      lineWidth: 5,
      trackColor: '#ADDDD05a',
      lineCap: 'circle',
      animate: 3000
  });
});