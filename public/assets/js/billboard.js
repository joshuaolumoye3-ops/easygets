// Polar Chart

const PolarChart = bb.generate({
    data: {
        columns: [
            ["data1", 60],
            ["data2", 120],
            ["data3", 75]
        ],
        type: "polar", // for ESM specify as: polar()
    },
    polar: {
        startingAngle: 1
    },
    bindto: "#startingAngle"
});

const AreaChart = bb.generate({
    title: {
        text: "Area step range"
    },
    data: {
        columns: [
            ["data1", [70, 40, 30],
                [155, 130, 115],
                [160, 135, 120],
                [200, 120, 110],
                [95, 50, 40],
                [199, 160, 125]
            ],
            ["data2", [350, 220, 110],
                [255, 230, 195],
                [260, 250, 190],
                [235, 220, 210],
                [180, 150, 100],
                [299, 260, 230]
            ]],
        type: "area-step-range", // for ESM specify as: areaStepRange()
    },
    tooltip: {
        show: false
    },
    bindto: "#areaRangeChart"
});


// Bar Chart 

const BarChart = bb.generate({
    data: {
        columns: [
            ["data1", -100, 100, 200, [-100, 0],
                [0, 100],
                [100, 200]
            ],
            ["data2", 100, 300, 500, [0, 100],
                [100, 300],
                [200, 500]
            ]],
        type: "bar", // for ESM specify as: bar()
    },
    bar: {
        width: {
            ratio: 0.5
        }
    },
    tooltip: {
        show: false
    },
    bindto: "#barChart"
});

setTimeout(function () {
    BarChart.load({
        columns: [
            ["data3", 200, 500, 800, [100, 200], [300, 500], [500, 800]],
        ],
        type: "bar", // for ESM specify as: bar()
    });
}, 1000);


// Funnel Chart 
var FunnelChart = bb.generate({
    data: {
        columns: [
            ["data1", 30],
            ["data2", 45],
            ["data3", 25],
            ["data4", 55]
        ],
        type: "funnel", // for ESM specify as: funnel()
        order: null,
        labels: {
            format: function (v, id, i, texts) {
                return id;
            }
        }
    },
    bindto: "#funnelChart_1"
});