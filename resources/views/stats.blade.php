<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM"
            crossorigin="anonymous"></script>
    <script src="{{asset('js/chartjs/dist/chart.umd.js')}}"></script>
    <title>Stats</title>
</head>
<body>
<div class="container p-5">

    <canvas id="line-chart" width="800" height="450"></canvas>

</div>

<script>
    var ports = JSON.parse('{!! json_encode($ports) !!}');
    var labels = [];
    var usage = [];
    var datasets = [];
    const colors = ['red', 'blue', 'green', 'purple', 'black', 'orange', 'lime', 'cyan', 'pink', 'darkblue'];
    for (var port in ports) {
        usage = [];
        for (const record of ports[port]) {
            labels.push(record['created_at']);
            usage.push(record['usage']);
        }
        datasets.push({
            data:usage,
            label:port,
            borderColor: colors[Math.ceil((Math.random() * 10))],
            fill: false
        });
    }
    new Chart(document.getElementById("line-chart"), {
        type: 'line',
        data: {
            labels: labels,
            datasets: datasets
        },
        options: {
            title: {
                display: true,
                text: 'World population per region (in millions)'
            }
        }
    });

</script>
</body>
</html>