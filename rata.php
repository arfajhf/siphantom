<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analisis Sensor & Rekomendasi Penyiraman</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .header p {
            font-size: 1.2em;
            opacity: 0.9;
        }

        .content {
            padding: 30px;
        }

        .form-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            border-left: 5px solid #4CAF50;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        input, select {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        input:focus, select:focus {
            outline: none;
            border-color: #4CAF50;
        }

        .btn-group {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(76, 175, 80, 0.4);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
            color: white;
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(33, 150, 243, 0.4);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            text-align: center;
            border-top: 4px solid #4CAF50;
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-value {
            font-size: 2.5em;
            font-weight: bold;
            color: #4CAF50;
            margin-bottom: 10px;
        }

        .stat-label {
            font-size: 1.1em;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .chart-container {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .chart-title {
            font-size: 1.5em;
            margin-bottom: 20px;
            color: #333;
            text-align: center;
        }

        .recommendations {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
        }

        .recommendation-item {
            background: white;
            padding: 20px;
            margin-bottom: 15px;
            border-radius: 10px;
            border-left: 4px solid #4CAF50;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .recommendation-title {
            font-size: 1.2em;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }

        .recommendation-text {
            color: #666;
            line-height: 1.6;
        }

        .loading {
            text-align: center;
            padding: 50px;
            color: #666;
        }

        .error {
            background: #ffebee;
            color: #c62828;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #c62828;
        }

        .success {
            background: #e8f5e8;
            color: #2e7d32;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #2e7d32;
        }

        @media (max-width: 768px) {
            .btn-group {
                flex-direction: column;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🌱 Smart Irrigation Analytics</h1>
            <p>Analisis Data Sensor & Rekomendasi Penyiraman Cerdas</p>
        </div>

        <div class="content">
            <div class="form-section">
                <h2>Pengaturan Analisis</h2>
                <div class="form-group">
                    <label for="deviceCode">Kode Device:</label>
                    <input type="text" id="deviceCode" placeholder="Masukkan kode device" required>
                </div>
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" placeholder="Masukkan username" required>
                </div>
                <div class="btn-group">
                    <button class="btn btn-primary" onclick="analyzeData()">📊 Analisis Data</button>
                    <button class="btn btn-secondary" onclick="refreshData()">🔄 Refresh Data</button>
                </div>
            </div>

            <div id="results" style="display: none;">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-value" id="avgTemp">--</div>
                        <div class="stat-label">Rata-rata Suhu (°C)</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value" id="avgHumidity">--</div>
                        <div class="stat-label">Rata-rata Kelembaban (%)</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value" id="dataCount">--</div>
                        <div class="stat-label">Jumlah Data</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value" id="lastUpdate">--</div>
                        <div class="stat-label">Update Terakhir</div>
                    </div>
                </div>

                <div class="chart-container">
                    <div class="chart-title">📈 Grafik Suhu dan Kelembaban</div>
                    <canvas id="sensorChart"></canvas>
                </div>

                <div class="recommendations">
                    <h2>🎯 Rekomendasi Penyiraman</h2>
                    <div id="recommendationsList"></div>
                </div>

                <div class="chart-container">
                    <div class="chart-title">⏰ Analisis Pola Penyiraman</div>
                    <canvas id="patternChart"></canvas>
                </div>
            </div>

            <div id="loading" class="loading" style="display: none;">
                <h3>🔄 Sedang menganalisis data...</h3>
                <p>Mohon tunggu sebentar</p>
            </div>

            <div id="error" style="display: none;"></div>
        </div>
    </div>

    <script>
        let sensorChart = null;
        let patternChart = null;
        let sensorData = [];

        async function analyzeData() {
            const deviceCode = document.getElementById('deviceCode').value;
            const username = document.getElementById('username').value;

            if (!deviceCode || !username) {
                showError('Mohon lengkapi kode device dan username');
                return;
            }

            showLoading(true);
            hideError();

            try {
                // Simulasi pengambilan data (dalam implementasi nyata, ganti dengan API call)
                const data = await fetchSensorData(deviceCode, username);
                
                if (data.length === 0) {
                    showError('Tidak ada data sensor yang ditemukan');
                    return;
                }

                // Analisis data
                const analysis = analyzeDataPoints(data);
                
                // Tampilkan hasil
                displayResults(analysis);
                
                // Buat grafik
                createCharts(data, analysis);
                
                // Generate rekomendasi
                generateRecommendations(analysis);

            } catch (error) {
                showError('Terjadi kesalahan: ' + error.message);
            } finally {
                showLoading(false);
            }
        }

        // Simulasi fetch data (ganti dengan API call sebenarnya)
        async function fetchSensorData(deviceCode, username) {
            // Dalam implementasi nyata, panggil API untuk mengambil 50 data terakhir
            // Contoh: const response = await fetch(`api.php?action=get_sensor_data&kode=${deviceCode}&username=${username}&limit=50`);
            
            // Simulasi data untuk demo
            const mockData = [];
            const now = new Date();
            
            for (let i = 49; i >= 0; i--) {
                const date = new Date(now - i * 3600000); // Data per jam
                mockData.push({
                    suhu: 25 + Math.random() * 10, // 25-35°C
                    kelembaban: 60 + Math.random() * 30, // 60-90%
                    timestamp: date.toISOString(),
                    tanggal: date.toISOString().split('T')[0],
                    waktu: date.toTimeString().split(' ')[0]
                });
            }
            
            return mockData;
        }

        function analyzeDataPoints(data) {
            const temperatures = data.map(d => parseFloat(d.suhu));
            const humidities = data.map(d => parseFloat(d.kelembaban));
            
            const avgTemp = temperatures.reduce((a, b) => a + b, 0) / temperatures.length;
            const avgHumidity = humidities.reduce((a, b) => a + b, 0) / humidities.length;
            
            const minTemp = Math.min(...temperatures);
            const maxTemp = Math.max(...temperatures);
            const minHumidity = Math.min(...humidities);
            const maxHumidity = Math.max(...humidities);
            
            // Analisis pola berdasarkan waktu
            const hourlyData = analyzeHourlyPattern(data);
            
            return {
                avgTemp: avgTemp.toFixed(1),
                avgHumidity: avgHumidity.toFixed(1),
                minTemp: minTemp.toFixed(1),
                maxTemp: maxTemp.toFixed(1),
                minHumidity: minHumidity.toFixed(1),
                maxHumidity: maxHumidity.toFixed(1),
                dataCount: data.length,
                lastUpdate: data[data.length - 1]?.timestamp || '',
                hourlyPattern: hourlyData,
                rawData: data
            };
        }

        function analyzeHourlyPattern(data) {
            const hourlyStats = {};
            
            data.forEach(point => {
                const hour = new Date(point.timestamp).getHours();
                if (!hourlyStats[hour]) {
                    hourlyStats[hour] = {
                        temps: [],
                        humidities: [],
                        count: 0
                    };
                }
                hourlyStats[hour].temps.push(parseFloat(point.suhu));
                hourlyStats[hour].humidities.push(parseFloat(point.kelembaban));
                hourlyStats[hour].count++;
            });
            
            // Hitung rata-rata per jam
            const hourlyAvg = {};
            Object.keys(hourlyStats).forEach(hour => {
                const stats = hourlyStats[hour];
                hourlyAvg[hour] = {
                    avgTemp: stats.temps.reduce((a, b) => a + b, 0) / stats.temps.length,
                    avgHumidity: stats.humidities.reduce((a, b) => a + b, 0) / stats.humidities.length,
                    count: stats.count
                };
            });
            
            return hourlyAvg;
        }

        function displayResults(analysis) {
            document.getElementById('avgTemp').textContent = analysis.avgTemp;
            document.getElementById('avgHumidity').textContent = analysis.avgHumidity;
            document.getElementById('dataCount').textContent = analysis.dataCount;
            document.getElementById('lastUpdate').textContent = new Date(analysis.lastUpdate).toLocaleString('id-ID');
            
            document.getElementById('results').style.display = 'block';
        }

        function createCharts(data, analysis) {
            createSensorChart(data);
            createPatternChart(analysis.hourlyPattern);
        }

        function createSensorChart(data) {
            const ctx = document.getElementById('sensorChart').getContext('2d');
            
            if (sensorChart) {
                sensorChart.destroy();
            }
            
            const labels = data.map(d => new Date(d.timestamp).toLocaleDateString('id-ID'));
            const tempData = data.map(d => parseFloat(d.suhu));
            const humidityData = data.map(d => parseFloat(d.kelembaban));
            
            sensorChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Suhu (°C)',
                        data: tempData,
                        borderColor: '#ff6384',
                        backgroundColor: 'rgba(255, 99, 132, 0.1)',
                        tension: 0.4,
                        fill: false
                    }, {
                        label: 'Kelembaban (%)',
                        data: humidityData,
                        borderColor: '#36a2eb',
                        backgroundColor: 'rgba(54, 162, 235, 0.1)',
                        tension: 0.4,
                        fill: false
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100
                        }
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        }
                    }
                }
            });
        }

        function createPatternChart(hourlyPattern) {
            const ctx = document.getElementById('patternChart').getContext('2d');
            
            if (patternChart) {
                patternChart.destroy();
            }
            
            const hours = Object.keys(hourlyPattern).sort((a, b) => parseInt(a) - parseInt(b));
            const tempData = hours.map(hour => hourlyPattern[hour].avgTemp);
            const humidityData = hours.map(hour => hourlyPattern[hour].avgHumidity);
            
            patternChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: hours.map(h => h + ':00'),
                    datasets: [{
                        label: 'Rata-rata Suhu per Jam',
                        data: tempData,
                        backgroundColor: 'rgba(255, 99, 132, 0.6)',
                        borderColor: '#ff6384',
                        borderWidth: 1
                    }, {
                        label: 'Rata-rata Kelembaban per Jam',
                        data: humidityData,
                        backgroundColor: 'rgba(54, 162, 235, 0.6)',
                        borderColor: '#36a2eb',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        function generateRecommendations(analysis) {
            const recommendations = [];
            
            // Rekomendasi berdasarkan suhu
            if (parseFloat(analysis.avgTemp) > 30) {
                recommendations.push({
                    title: '🌡️ Suhu Tinggi Terdeteksi',
                    text: `Rata-rata suhu ${analysis.avgTemp}°C cukup tinggi. Rekomendasi: Tingkatkan frekuensi penyiraman pada pagi hari (06:00-08:00) dan sore hari (17:00-19:00) untuk menurunkan suhu tanah.`
                });
            } else if (parseFloat(analysis.avgTemp) < 25) {
                recommendations.push({
                    title: '🌡️ Suhu Rendah Terdeteksi',
                    text: `Rata-rata suhu ${analysis.avgTemp}°C relatif rendah. Rekomendasi: Kurangi frekuensi penyiraman dan lakukan penyiraman pada siang hari (10:00-14:00) untuk menjaga kehangatan tanah.`
                });
            }
            
            // Rekomendasi berdasarkan kelembaban
            if (parseFloat(analysis.avgHumidity) < 70) {
                recommendations.push({
                    title: '💧 Kelembaban Rendah',
                    text: `Kelembaban rata-rata ${analysis.avgHumidity}% di bawah optimal. Rekomendasi: Tambahkan jadwal penyiraman tambahan setiap 4-6 jam dengan durasi 30-45 detik.`
                });
            } else if (parseFloat(analysis.avgHumidity) > 85) {
                recommendations.push({
                    title: '💧 Kelembaban Tinggi',
                    text: `Kelembaban rata-rata ${analysis.avgHumidity}% cukup tinggi. Rekomendasi: Kurangi durasi penyiraman menjadi 15-20 detik dan pastikan drainase yang baik.`
                });
            }
            
            // Rekomendasi jadwal optimal
            const optimalTimes = getOptimalWateringTimes(analysis.hourlyPattern);
            recommendations.push({
                title: '⏰ Jadwal Penyiraman Optimal',
                text: `Berdasarkan analisis pola, waktu terbaik untuk penyiraman: ${optimalTimes.join(', ')}. Hindari penyiraman pada jam 11:00-15:00 saat suhu tertinggi.`
            });
            
            // Rekomendasi durasi
            const recommendedDuration = calculateOptimalDuration(analysis);
            recommendations.push({
                title: '⏱️ Durasi Penyiraman',
                text: `Durasi penyiraman yang disarankan: ${recommendedDuration} detik per sesi. Sesuaikan berdasarkan kondisi cuaca dan musim.`
            });
            
            displayRecommendations(recommendations);
        }

        function getOptimalWateringTimes(hourlyPattern) {
            const times = [];
            const hours = Object.keys(hourlyPattern);
            
            // Cari jam dengan suhu rendah dan kelembaban rendah
            hours.forEach(hour => {
                const data = hourlyPattern[hour];
                if (data.avgTemp < 28 && data.avgHumidity < 80) {
                    times.push(hour + ':00');
                }
            });
            
            // Default times jika tidak ada data yang memenuhi kriteria
            if (times.length === 0) {
                times.push('06:00', '18:00');
            }
            
            return times.slice(0, 3); // Maksimal 3 waktu
        }

        function calculateOptimalDuration(analysis) {
            const avgTemp = parseFloat(analysis.avgTemp);
            const avgHumidity = parseFloat(analysis.avgHumidity);
            
            let baseDuration = 30; // detik
            
            // Sesuaikan berdasarkan suhu
            if (avgTemp > 30) baseDuration += 15;
            if (avgTemp < 25) baseDuration -= 10;
            
            // Sesuaikan berdasarkan kelembaban
            if (avgHumidity < 70) baseDuration += 10;
            if (avgHumidity > 85) baseDuration -= 15;
            
            return Math.max(15, Math.min(60, baseDuration)); // Batasi 15-60 detik
        }

        function displayRecommendations(recommendations) {
            const container = document.getElementById('recommendationsList');
            container.innerHTML = '';
            
            recommendations.forEach(rec => {
                const div = document.createElement('div');
                div.className = 'recommendation-item';
                div.innerHTML = `
                    <div class="recommendation-title">${rec.title}</div>
                    <div class="recommendation-text">${rec.text}</div>
                `;
                container.appendChild(div);
            });
        }

        function refreshData() {
            const deviceCode = document.getElementById('deviceCode').value;
            const username = document.getElementById('username').value;
            
            if (deviceCode && username) {
                analyzeData();
            }
        }

        function showLoading(show) {
            document.getElementById('loading').style.display = show ? 'block' : 'none';
            document.getElementById('results').style.display = show ? 'none' : 'block';
        }

        function showError(message) {
            const errorDiv = document.getElementById('error');
            errorDiv.className = 'error';
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
        }

        function hideError() {
            document.getElementById('error').style.display = 'none';
        }

        // Auto-refresh setiap 5 menit jika ada data
        setInterval(() => {
            const deviceCode = document.getElementById('deviceCode').value;
            const username = document.getElementById('username').value;
            
            if (deviceCode && username && document.getElementById('results').style.display === 'block') {
                refreshData();
            }
        }, 300000); // 5 menit
    </script>
</body>
</html>