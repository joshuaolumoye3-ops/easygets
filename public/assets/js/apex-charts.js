// Line Chart 

const LineChartConfig = {
    series: [
        {
            name: "Sales",
            data: [50, 40, 300, 320, 500, 350, 200, 230, 500],
        },
    ],
    chart: {
        type: "line",
        height: 240,
        toolbar: {
            show: false,
        },
    },
    title: {
        show: "",
    },
    dataLabels: {
        enabled: false,
    },
    colors: ["#020617"],
    stroke: {
        lineCap: "round",
        curve: "smooth",
    },
    markers: {
        size: 0,
    },
    xaxis: {
        axisTicks: {
            show: false,
        },
        axisBorder: {
            show: false,
        },
        labels: {
            style: {
                colors: "#616161",
                fontSize: "12px",
                fontFamily: "inherit",
                fontWeight: 400,
            },
        },
        categories: [
            "Apr",
            "May",
            "Jun",
            "Jul",
            "Aug",
            "Sep",
            "Oct",
            "Nov",
            "Dec",
        ],
    },
    yaxis: {
        labels: {
            style: {
                colors: "#616161",
                fontSize: "12px",
                fontFamily: "inherit",
                fontWeight: 400,
            },
        },
    },
    grid: {
        show: true,
        borderColor: "#dddddd",
        strokeDashArray: 5,
        xaxis: {
            lines: {
                show: true,
            },
        },
        padding: {
            top: 5,
            right: 20,
        },
    },
    fill: {
        opacity: 0.8,
    },
    tooltip: {
        theme: "dark",
    },
};

const LineChart = new ApexCharts(document.querySelector("#line-chart"), LineChartConfig);

LineChart.render();


// Bar Char 

const BarChartConfig = {
    series: [
        {
            name: "Sales",
            data: [50, 40, 300, 320, 500, 350, 200, 230, 500],
        },
    ],
    chart: {
        type: "bar",
        height: 240,
        toolbar: {
            show: false,
        },
    },
    title: {
        show: "",
    },
    dataLabels: {
        enabled: false,
    },
    colors: ["#020617"],
    plotOptions: {
        bar: {
            columnWidth: "40%",
            borderRadius: 2,
        },
    },
    xaxis: {
        axisTicks: {
            show: false,
        },
        axisBorder: {
            show: false,
        },
        labels: {
            style: {
                colors: "#616161",
                fontSize: "12px",
                fontFamily: "inherit",
                fontWeight: 400,
            },
        },
        categories: [
            "Apr",
            "May",
            "Jun",
            "Jul",
            "Aug",
            "Sep",
            "Oct",
            "Nov",
            "Dec",
        ],
    },
    yaxis: {
        labels: {
            style: {
                colors: "#616161",
                fontSize: "12px",
                fontFamily: "inherit",
                fontWeight: 400,
            },
        },
    },
    grid: {
        show: true,
        borderColor: "#dddddd",
        strokeDashArray: 5,
        xaxis: {
            lines: {
                show: true,
            },
        },
        padding: {
            top: 5,
            right: 20,
        },
    },
    fill: {
        opacity: 0.8,
    },
    tooltip: {
        theme: "dark",
    },
};

const BarChart = new ApexCharts(document.querySelector("#bar-chart"), BarChartConfig);

BarChart.render();

// Pie Chart 

const PieChartConfig = {
    series: [44, 55, 13, 43, 22],
    chart: {
        type: "pie",
        width: 280,
        height: 280,
        toolbar: {
            show: false,
        },
    },
    title: {
        show: "",
    },
    dataLabels: {
        enabled: false,
    },
    colors: ["#020617", "#ff8f00", "#00897b", "#1e88e5", "#d81b60"],
    legend: {
        show: false,
    },
};

const PieChart = new ApexCharts(document.querySelector("#pie-chart"), PieChartConfig);

PieChart.render();


// Radar Chart 

var RadarChartConfig = {
    series: [{
        name: 'Series 1',
        data: [80, 50, 30, 40, 100, 20],
    }, {
        name: 'Series 2',
        data: [20, 30, 40, 80, 20, 80],
    }, {
        name: 'Series 3',
        data: [44, 76, 78, 13, 43, 10],
    }],
    chart: {
        height: 300,
        type: 'radar',
        dropShadow: {
            enabled: true,
            blur: 1,
            left: 1,
            top: 1
        },
        toolbar: {
            show: false
        }
    },
    legend: {
        show: false
    },
    stroke: {
        width: 2
    },
    fill: {
        opacity: 0.1
    },
    markers: {
        size: 0
    },
    yaxis: {
        stepSize: 20
    },
    xaxis: {
        categories: ['2011', '2012', '2013', '2014', '2015', '2016']
    }

};

var RadarChart = new ApexCharts(document.querySelector("#radar-chart"), RadarChartConfig);
RadarChart.render();