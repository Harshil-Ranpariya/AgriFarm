<?php
// Weather configuration for Farmer Dashboard
// API: OpenWeatherMap

if (!defined('WEATHER_API_KEY')) {
	define('WEATHER_API_KEY', '00f0e94087dd620dc14003cc1b000de1');
}

if (!defined('WEATHER_API_URL')) {
	define('WEATHER_API_URL', 'https://api.openweathermap.org/data/2.5');
}

if (!defined('WEATHER_DEFAULT_LOCATION')) {
	define('WEATHER_DEFAULT_LOCATION', 'Mumbai');
}

if (!defined('WEATHER_AUTO_REFRESH_INTERVAL')) {
	// 5 minutes (in milliseconds) for client-side JS setInterval
	define('WEATHER_AUTO_REFRESH_INTERVAL', 300000);
}

if (!defined('WEATHER_UNITS')) {
	// Use 'metric' for Celsius, 'imperial' for Fahrenheit
	define('WEATHER_UNITS', 'metric');
}

if (!defined('WEATHER_REQUEST_TIMEOUT')) {
	// Request timeout in milliseconds
	define('WEATHER_REQUEST_TIMEOUT', 15000);
}

if (!defined('WEATHER_SHOW_PAST_DAYS')) {
	define('WEATHER_SHOW_PAST_DAYS', true);
}

if (!defined('WEATHER_SHOW_FORECAST')) {
	define('WEATHER_SHOW_FORECAST', true);
}

if (!defined('WEATHER_FORECAST_DAYS')) {
	define('WEATHER_FORECAST_DAYS', 5);
}

if (!function_exists('getWeatherColorsArray')) {
	function getWeatherColorsArray() {
		return [
			'sunny' => ['start' => '#f6d365', 'end' => '#fda085'],
			'cloudy' => ['start' => '#cfd9df', 'end' => '#e2ebf0'],
			'rain' => ['start' => '#74ebd5', 'end' => '#ACB6E5'],
			'storm' => ['start' => '#bdc3c7', 'end' => '#2c3e50'],
			'snow' => ['start' => '#e6f0ff', 'end' => '#b3d4fc'],
			'default' => ['start' => '#667eea', 'end' => '#764ba2'],
		];
	}
}

?>
