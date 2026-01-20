<?php
date_default_timezone_set('America/Denver'); // change if needed
$refreshtime = "1800"; // refresh in seconds
$iframeurl = ""; //google calendar url
$weather_lat = ";
$weather_lon = "";
$weather_timezone  = 'America/Denver';

$weather_api_url = "https://api.open-meteo.com/v1/forecast"
     . "?latitude={$weather_lat}"
     . "&longitude={$weather_lon}"
     . "&current_weather=true"
     . "&daily=weathercode,temperature_2m_max,temperature_2m_min"
     . "&temperature_unit=fahrenheit"
     . "&timezone={$weather_timezone}";

$weather_api_response = file_get_contents($weather_api_url);
$weather_data = json_decode($weather_api_response, true);

function weather_icon($code) {
    return match (true) {
        $code === 0 => ['☀️', 'Sunny'],
        in_array($code, [1, 2]) => ['⛅', 'Partly Cloudy'],
        $code === 3 => ['☁️', 'Cloudy'],
        in_array($code, [45, 48]) => ['🌫️', 'Fog'],
        in_array($code, [51, 53, 55, 61, 63, 65]) => ['🌧️', 'Rain'],
        in_array($code, [71, 73, 75, 77]) => ['❄️', 'Snow'],
        in_array($code, [80, 81, 82]) => ['🌦️', 'Showers'],
        in_array($code, [95, 96, 99]) => ['⛈️', 'Storms'],
        default => ['❓', 'Unknown']
    };
} 
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Dashboard</title>
<meta http-equiv="refresh" content="<?PHP echo $refreshtime; ?>">
<style>
    html, body {
        margin: 0;
        padding: 0;
        height: 100%;
        font-family: Arial, sans-serif;
        background: #111;
        color: #fff;
    }

    .container {
        display: flex;
        flex-direction: column;
        height: 100vh;
    }

    .top {
        height: 5%;
        align-items: center;
        background: #222;
    }

    .middle {
        height: 90%;
    }

    .middle iframe {
        width: 100%;
        height: 100%;
        border: none;
    }

    .bottom {
        height: 5%;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 20px;
        background: #222;
        font-size: 1.5em;
    }
	.current-weather {
		font-size: 2em;
		position: absolute;
		top: 5px; 
		right: 5px;  
		z-index: 100; 
	}
	.datetime {
		font-size: 2.5em;
	}
	.weather-day {
		display: flex;
		align-items: center;
		gap: 8px;
	}

	.weather-icon {
		font-size: 1.3em;
	}

	.weather-temp {
		opacity: 0.9;
	}
	#refresh-bar {
		position: fixed;
		bottom: 0;
		left: 0;
		width: 100%;
		height: 2px;
		background: rgba(255,255,255,0.15);
		z-index: 9999;
	}

	#refresh-progress {
		height: 100%;
		width: 100%;
		background: #ef4444;
		animation: countdown linear forwards;
	}
	@keyframes countdown {
		from {
			width: 100%;
		}
		to {
			width: 0%;
		}
	}
</style>
</head>
<div id="refresh-bar">
    <div id="refresh-progress"></div>
</div>
<body>
<div class="container">


    <!-- TOP -->
    <div class="top">
        <div class="datetime" id="datetime"><?php echo date('l, F j Y — g:i:s A'); ?></div>
		<?php
		$current = $weather_data['current_weather'];
		[$icon, $text] = weather_icon($current['weathercode']);
		?>
    </div>

	<div class="current-weather" id="current-weather">
		<span><?= round($current['temperature']) ?>°F</span>
	</div>

    <!-- MIDDLE -->
    <div class="middle">
        <iframe src="<?= $iframeurl; ?>"></iframe>
    </div>

    <!-- BOTTOM -->
	<div class="bottom" id="weather">
		<?php
		for ($i = 0; $i < 5; $i++) {

			// Parse date SAFELY as local (no UTC bug)
			[$year, $month, $day] = explode('-', $weather_data['daily']['time'][$i]);
			$date = mktime(0, 0, 0, $month, $day, $year);

			$code = $weather_data['daily']['weathercode'][$i];
			$max  = round($weather_data['daily']['temperature_2m_max'][$i]);
			$min  = round($weather_data['daily']['temperature_2m_min'][$i]);

			[$icon, $text] = weather_icon($code);

			$label = ($i === 0)
				? 'Today'
				: date('D', $date);
			?>
			
			<div class="weather-day">
				<span class="weather-icon"><?= $icon ?></span>
				<strong><?= $label ?></strong>
				<span><?= $text ?></span>
				<span class="weather-temp"><?= $max ?>°F / <?= $min ?>°F</span>
			</div>

		<?php } ?>
	</div>
<script>
const REFRESH_SECONDS = <?= $refreshtime; ?>;

const progress = document.getElementById("refresh-progress");
progress.style.animationDuration = `${REFRESH_SECONDS}s`;

// Force restart animation if page is cached
progress.style.animationName = "none";
requestAnimationFrame(() => {
    progress.style.animationName = "countdown";
});
</script>	
<script>
/* Live clock */
setInterval(() => {
    const now = new Date();
    document.getElementById("datetime").innerText =
        now.toLocaleDateString(undefined, {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        }) + " — " + now.toLocaleTimeString();
}, 1000);
</script>
</body>
</html>
