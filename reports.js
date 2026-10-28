function fetchData() {
    var location = document.getElementById('locationFilter').value;
    var startDate = document.getElementById('startDate').value;
    var endDate = document.getElementById('endDate').value;

    // Construct the URL with query parameters
    var url = `get_reports.php?location=${encodeURIComponent(location)}&start_date=${encodeURIComponent(startDate)}&end_date=${encodeURIComponent(endDate)}`;

    // Fetch data from the server
    fetch(url)
        .then(response => response.json())
        .then(data => {
            drawChart(data); // Use the returned data to draw the chart
        })
        .catch(error => console.error('Error fetching data:', error));
}
then(data => {
    console.log('Data received:', data);

    // Check if the response is empty
    if (data.length === 0) {
        alert("No data found for the selected filters.");
    } else {
        drawChart(data);
    }
})
function drawCharts(data) {
    console.log("API Response:", data);

    // Check if the data is correctly structured
    if (!data.animal_data || !data.cruelty_data || !data.case_status_data) {
        console.error("Invalid data structure:", data);
        return;
    }

    // 1. Draw Animal Type Bar Chart
    var animalData = google.visualization.arrayToDataTable([
        ['Animal Type', 'Number of Reports'],
        ...data.animal_data
    ]);

    var animalOptions = {
        title: 'Cruelty Reports by Animal Type',
        hAxis: { title: 'Animal Type' },
        vAxis: { title: 'Number of Reports' },
        bars: 'vertical'
    };

    var animalChart = new google.visualization.BarChart(document.getElementById('animal_chart'));
    animalChart.draw(animalData, animalOptions);

    // 2. Draw Cruelty Type Bar Chart
    var crueltyData = google.visualization.arrayToDataTable([
        ['Cruelty Type', 'Number of Reports'],
        ...data.cruelty_data
    ]);

    var crueltyOptions = {
        title: 'Cruelty Reports by Type',
        hAxis: { title: 'Cruelty Type' },
        vAxis: { title: 'Number of Reports' },
        bars: 'vertical'
    };

    var crueltyChart = new google.visualization.BarChart(document.getElementById('cruelty_chart'));
    crueltyChart.draw(crueltyData, crueltyOptions);

    // 3. Draw Case Status Bar Chart
    var caseStatusData = google.visualization.arrayToDataTable([
        ['Case Status', 'Number of Investigations'],
        ...data.case_status_data
    ]);

    var caseStatusOptions = {
        title: 'Investigation Case Status',
        hAxis: { title: 'Case Status' },
        vAxis: { title: 'Number of Investigations' },
        bars: 'vertical'
    };

    var caseStatusChart = new google.visualization.BarChart(document.getElementById('case_status_chart'));
    caseStatusChart.draw(caseStatusData, caseStatusOptions);
}

function fetchDataAndDrawCharts() {
    var location = document.getElementById('locationFilter').value;
    var startDate = document.getElementById('startDate').value;
    var endDate = document.getElementById('endDate').value;

    // Fetch data from backend (use AJAX)
    fetch(`get_report_data.php?location=${location}&start_date=${startDate}&end_date=${endDate}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            console.log('API Response:', data);
            drawCharts(data);
        })
        .catch(error => {
            console.error('Error fetching data:', error);
        });
}

google.charts.setOnLoadCallback(fetchDataAndDrawCharts);


