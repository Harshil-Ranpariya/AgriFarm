<?php
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/weather_config.php';
if (!isset($_SESSION['farmer_id'])) { header('Location: Login.html'); exit(); }

$fid = $_SESSION['farmer_id'];
$farmerInfo = $conn->query("SELECT * FROM farmer_users WHERE id=".(int)$fid)->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Weather Monitoring - Farmer Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link rel="stylesheet" href="farmer_home.css" />
  <link rel="stylesheet" href="../../responsive_menu.css" />
</head>
<body>
  <!-- Dark Green Header -->
  <div class="dashboard-header">
    <div class="header-left">
      <i class="fas fa-seedling"></i>
      <div class="header-title">
        <h2>Farmer Dashboard</h2>
        <small>Manage your farm products and harvest.</small>
      </div>
    </div>
    <div class="header-right">
      <a href="farmer_add_product.php" class="btn-bidding" style="background: #4CAF50;"><i class="fas fa-plus-circle"></i> Add Product</a>
      <a href="farmer_product_ratings.php" class="btn-bidding" style="background: #ff9800;"><i class="fas fa-star"></i> Product Rating</a>
      <a href="farmer_feedback.php" class="btn-bidding" style="background: #8e24aa;"><i class="fas fa-thumbs-up"></i> Website Rating</a>
      <a href="farmer_weather.php" class="btn-bidding active" style="background: #2196F3;"><i class="fas fa-cloud-sun"></i> Weather</a>
      <a href="farmer_bidding.php" class="btn-bidding"><i class="fas fa-gavel"></i> Bidding</a>
      <a href="farmer_return_products.php" class="btn-bidding" style="background: #e91e63;"><i class="fas fa-undo"></i> Return Products</a>
      <div class="dropdown">
        <button type="button" class="btn btn-light dropdown-toggle" style="background:#ffffff;color:#2e7d32;border:none;border-radius:999px;font-weight:600;display:flex;align-items:center;gap:8px;" id="farmerProfileDropdown" data-bs-toggle="dropdown" aria-expanded="false" title="<?php echo esc($farmerInfo['email'] ?? ''); ?>">
          <i class="fas fa-user-circle"></i>
          <span><?php echo esc($farmerInfo['username'] ?? 'Farmer'); ?></span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="farmerProfileDropdown">
          <li>
            <button class="dropdown-item" type="button" data-bs-toggle="modal" data-bs-target="#farmerProfileModal">
              <i class="fas fa-id-card me-2"></i> View Profile
            </button>
          </li>
          <li><hr class="dropdown-divider"></li>
          <li>
            <a class="dropdown-item text-danger" href="logout.php">
              <i class="fas fa-sign-out-alt me-2"></i> Logout
            </a>
          </li>
        </ul>
      </div>
    </div>
  </div>

  <div class="main-container">
    <!-- Weather Monitoring Section -->
    <div class="section-card" id="weatherSection">
      <div class="section-header weather-header">
        <i class="fas fa-cloud-sun"></i> Live Weather Monitoring
      </div>
      <div class="section-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <div></div>
        <div>
          <button class="btn btn-sm btn-outline-success" id="refreshWeather" onclick="fetchWeatherData()">
            <i class="fas fa-sync-alt"></i> Refresh
          </button>
          <small class="text-muted ms-2" id="lastUpdate"></small>
        </div>
      </div>
      
      <!-- Location Input -->
      <div class="mb-3">
        <div class="row g-2">
          <div class="col-md-6">
            <label class="form-label"><i class="fas fa-map-marker-alt"></i> Location</label>
            <input type="text" class="form-control" id="weatherLocation" placeholder="Enter city name (e.g., Mumbai, Delhi)" value="Mumbai">
          </div>
          <div class="col-md-6 d-flex align-items-end">
            <button class="btn btn-success w-100" onclick="updateLocation()">
              <i class="fas fa-search"></i> Get Weather
            </button>
          </div>
        </div>
      </div>

      <div id="weatherLoading" class="text-center py-5" style="display: none;">
        <div class="spinner-border text-success" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-2 text-muted">Fetching weather data...</p>
      </div>

      <div id="weatherError" class="alert alert-warning" style="display: none;"></div>

      <div id="currentWeather" style="display: none;">
        <div class="weather-current-card p-4 mb-4 rounded">
          <div class="row align-items-center">
            <div class="col-md-8">
              <h3 class="mb-2"><i class="fas fa-map-marker-alt"></i> <span id="currentLocation"></span></h3>
              <div class="d-flex align-items-center">
                <div class="display-1 mb-0" id="currentTemp">--</div>
                <div class="ms-3">
                  <div class="h5 mb-0" id="currentDesc">--</div>
                  <div class="small"><i class="fas fa-thermometer-half"></i> Feels like <span id="currentFeelsLike">--</span>°C</div>
                </div>
              </div>
            </div>
            <div class="col-md-4 text-center">
              <i class="fas fa-cloud-sun display-1 mb-3" id="currentIcon"></i>
              <div class="row g-2 mt-3">
                <div class="col-6">
                  <div class="small"><i class="fas fa-tint"></i> Humidity</div>
                  <div class="fw-bold" id="currentHumidity">--%</div>
                </div>
                <div class="col-6">
                  <div class="small"><i class="fas fa-wind"></i> Wind</div>
                  <div class="fw-bold" id="currentWind">-- km/h</div>
                </div>
                <div class="col-6">
                  <div class="small"><i class="fas fa-eye"></i> Visibility</div>
                  <div class="fw-bold" id="currentVisibility">-- km</div>
                </div>
                <div class="col-6">
                  <div class="small"><i class="fas fa-compress-arrows-alt"></i> Pressure</div>
                  <div class="fw-bold" id="currentPressure">-- hPa</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="mb-4">
          <h6 class="mb-3"><i class="fas fa-history"></i> Past 3 Days Weather</h6>
          <div class="row g-3" id="pastWeatherCards"></div>
        </div>

        <div>
          <h6 class="mb-3"><i class="fas fa-calendar-alt"></i> 5-Day Forecast</h6>
          <div class="row g-3" id="forecastCards"></div>
        </div>
      </div>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../../responsive_menu.js"></script>
  <script>
    // Initialize Bootstrap dropdown properly
    document.addEventListener('DOMContentLoaded', function() {
      const dropdownButton = document.getElementById('farmerProfileDropdown');
      if (dropdownButton) {
        new bootstrap.Dropdown(dropdownButton);
      }
    });
    
    // Ensure dropdown menu is always clickable and visible
    setTimeout(function() {
      const dropdownButton = document.getElementById('farmerProfileDropdown');
      const dropdownMenu = document.querySelector('.dropdown-menu');
      
      if (dropdownButton) {
        dropdownButton.style.position = 'relative';
        dropdownButton.style.zIndex = '10002';
        dropdownButton.style.pointerEvents = 'auto';
      }
      
      if (dropdownMenu) {
        dropdownMenu.style.pointerEvents = 'auto';
        dropdownMenu.style.visibility = 'visible';
        dropdownMenu.style.zIndex = '10001';
        dropdownMenu.style.position = 'absolute';
      }
    }, 100);
    
    // Prevent responsive menu overlay from blocking dropdown
    document.addEventListener('click', function(e) {
      const dropdownButton = document.getElementById('farmerProfileDropdown');
      const dropdownMenu = document.querySelector('.dropdown-menu');
      const overlay = document.getElementById('menuOverlay');
      
      // If clicking dropdown button or items, ensure overlay doesn't block
      if (dropdownButton && (e.target === dropdownButton || dropdownButton.contains(e.target))) {
        if (overlay) overlay.style.zIndex = '9998';
        if (dropdownMenu) dropdownMenu.style.zIndex = '10001';
      }
      
      // Ensure dropdown items are clickable
      if (e.target.classList.contains('dropdown-item')) {
        e.stopPropagation();
      }
    });
  </script>
  
  <script>
    const WEATHER_API_KEY = '<?php echo esc(WEATHER_API_KEY); ?>';
    const WEATHER_API_URL = '<?php echo WEATHER_API_URL; ?>';
    const DEFAULT_LOCATION = '<?php echo esc(WEATHER_DEFAULT_LOCATION); ?>';
    const AUTO_REFRESH_INTERVAL = <?php echo WEATHER_AUTO_REFRESH_INTERVAL; ?>;
    const WEATHER_UNITS = '<?php echo WEATHER_UNITS; ?>';
    const WEATHER_TIMEOUT = <?php echo WEATHER_REQUEST_TIMEOUT; ?>;
    
    const WEATHER_COLORS = <?php echo json_encode(getWeatherColorsArray()); ?>;
    
    const SHOW_PAST_DAYS = <?php echo WEATHER_SHOW_PAST_DAYS ? 'true' : 'false'; ?>;
    const SHOW_FORECAST = <?php echo WEATHER_SHOW_FORECAST ? 'true' : 'false'; ?>;
    const FORECAST_DAYS = <?php echo WEATHER_FORECAST_DAYS; ?>;
    
    let isDemoMode = false;
    let weatherRefreshInterval = null;
    
    function isApiKeyValid() {
      if (!WEATHER_API_KEY || WEATHER_API_KEY.trim() === '') {
        return false;
      }
      if (WEATHER_API_KEY.includes('YOUR_OPENWEATHERMAP_API_KEY_HERE') || 
          WEATHER_API_KEY.toLowerCase().includes('your_api_key') ||
          WEATHER_API_KEY.toLowerCase().includes('placeholder')) {
        return false;
      }
      if (WEATHER_API_KEY.length < 20) {
        return false;
      }
      return true;
    }
    
    document.addEventListener('DOMContentLoaded', function() {
      console.log('Weather system initializing...');
      console.log('API Key configured:', isApiKeyValid() ? 'YES ✓' : 'NO ✗');
      
      if (isApiKeyValid()) {
        console.log('API Key is valid, fetching real weather data...');
        fetchWeatherData();
        if (weatherRefreshInterval) clearInterval(weatherRefreshInterval);
        weatherRefreshInterval = setInterval(function() {
          console.log('Auto-refreshing weather data...');
          fetchWeatherData();
        }, AUTO_REFRESH_INTERVAL); 
      } else {
        console.warn('API Key invalid or not configured, showing demo mode');
        showDemoWeather();
        showWeatherError('⚠ Demo Mode: API key not properly configured. Please set your OpenWeatherMap API key. <a href="WEATHER_SETUP_GUIDE.md" target="_blank" class="alert-link">Click here for setup instructions</a>');
      }
    });

    function updateLocation() {
      if (!isApiKeyValid()) {
        showDemoWeather();
        showWeatherError('⚠ Demo Mode: Configure your API key to get real weather data for different locations.');
      } else {
        fetchWeatherData();
      }
    }

    function showDemoWeather() {
      isDemoMode = true;
      const location = document.getElementById('weatherLocation').value || DEFAULT_LOCATION;
      const loadingEl = document.getElementById('weatherLoading');
      const errorEl = document.getElementById('weatherError');
      const currentWeatherEl = document.getElementById('currentWeather');
      
      loadingEl.style.display = 'block';
      errorEl.style.display = 'none';
      currentWeatherEl.style.display = 'none';
      
      setTimeout(() => {
        const demoCurrentData = {
          name: location,
          sys: { country: 'IN' },
          main: {
            temp: 28,
            feels_like: 30,
            humidity: 65,
            pressure: 1013
          },
          weather: [{
            main: 'Clear',
            description: 'clear sky',
            icon: '01d'
          }],
          wind: { speed: 3.5 },
          visibility: 10000
        };
        
        const demoForecastData = {
          list: [
            { dt: Date.now()/1000 + 86400, main: { temp: 29, humidity: 62 }, weather: [{ description: 'sunny', icon: '01d' }], wind: { speed: 4.2 } },
            { dt: Date.now()/1000 + 172800, main: { temp: 27, humidity: 68 }, weather: [{ description: 'partly cloudy', icon: '02d' }], wind: { speed: 3.8 } },
            { dt: Date.now()/1000 + 259200, main: { temp: 26, humidity: 72 }, weather: [{ description: 'cloudy', icon: '04d' }], wind: { speed: 3.5 } },
            { dt: Date.now()/1000 + 345600, main: { temp: 28, humidity: 65 }, weather: [{ description: 'clear sky', icon: '01d' }], wind: { speed: 4.0 } },
            { dt: Date.now()/1000 + 432000, main: { temp: 30, humidity: 60 }, weather: [{ description: 'sunny', icon: '01d' }], wind: { speed: 4.5 } }
          ]
        };
        
        displayCurrentWeather(demoCurrentData);
        displayForecast(demoForecastData);
        displayPastDays(demoForecastData, demoCurrentData);
        
        document.getElementById('lastUpdate').textContent = 'Demo Mode - Last updated: ' + new Date().toLocaleTimeString();
        
        loadingEl.style.display = 'none';
        currentWeatherEl.style.display = 'block';
      }, 500);
    }

    async function fetchWeatherData() {
      const location = document.getElementById('weatherLocation').value || DEFAULT_LOCATION;
      const loadingEl = document.getElementById('weatherLoading');
      const errorEl = document.getElementById('weatherError');
      const currentWeatherEl = document.getElementById('currentWeather');
      const refreshBtn = document.getElementById('refreshWeather');
      
      loadingEl.style.display = 'block';
      errorEl.style.display = 'none';
      currentWeatherEl.style.display = 'none';
      refreshBtn.classList.add('loading');
      refreshBtn.disabled = true;

      try {
        if (!isApiKeyValid()) {
          throw new Error('API key not configured. Please set your OpenWeatherMap API key in weather_config.php');
        }

        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), WEATHER_TIMEOUT);
        
        let currentResponse;
        try {
          const weatherUrl = `${WEATHER_API_URL}/weather?q=${encodeURIComponent(location)}&appid=${WEATHER_API_KEY}&units=${WEATHER_UNITS}`;
          console.log('Fetching current weather from:', weatherUrl.replace(WEATHER_API_KEY, 'API_KEY_HIDDEN'));
          
          currentResponse = await fetch(weatherUrl, { 
            signal: controller.signal,
            method: 'GET',
            headers: {
              'Accept': 'application/json'
            }
          });
        } catch (fetchError) {
          if (fetchError.name === 'AbortError') {
            throw new Error('Request timeout. Please check your internet connection and try again.');
          }
          throw new Error('Network error: ' + fetchError.message);
        } finally {
          clearTimeout(timeoutId);
        }
        
        if (!currentResponse.ok) {
          const errorData = await currentResponse.json().catch(() => ({}));
          if (currentResponse.status === 401) {
            throw new Error('Invalid API key. Please check your OpenWeatherMap API key in weather_config.php');
          } else if (currentResponse.status === 404) {
            throw new Error(`Location "${location}" not found. Please check the city name.`);
          } else if (currentResponse.status === 429) {
            throw new Error('Too many requests. Please wait a moment and try again.');
          } else {
            throw new Error(errorData.message || `Unable to fetch weather data (Status: ${currentResponse.status}). Please try again.`);
          }
        }
        
        const currentData = await currentResponse.json();
        
        if (!currentData || !currentData.main || !currentData.weather || !currentData.weather[0]) {
          throw new Error('Invalid weather data received from API.');
        }

        const forecastController = new AbortController();
        const forecastTimeoutId = setTimeout(() => forecastController.abort(), WEATHER_TIMEOUT);
        
        let forecastResponse;
        try {
          const forecastUrl = `${WEATHER_API_URL}/forecast?q=${encodeURIComponent(location)}&appid=${WEATHER_API_KEY}&units=${WEATHER_UNITS}`;
          console.log('Fetching forecast from:', forecastUrl.replace(WEATHER_API_KEY, 'API_KEY_HIDDEN'));
          
          forecastResponse = await fetch(forecastUrl, { 
            signal: forecastController.signal,
            method: 'GET',
            headers: {
              'Accept': 'application/json'
            }
          });
        } catch (fetchError) {
          if (fetchError.name === 'AbortError') {
            throw new Error('Forecast request timeout. Please check your internet connection.');
          }
          throw new Error('Forecast network error: ' + fetchError.message);
        } finally {
          clearTimeout(forecastTimeoutId);
        }
        
        if (!forecastResponse.ok) {
          const errorData = await forecastResponse.json().catch(() => ({}));
          if (forecastResponse.status === 401) {
            console.warn('Invalid API key for forecast. Showing current weather only.');
          } else if (forecastResponse.status === 429) {
            console.warn('Too many requests for forecast. Showing current weather only.');
          } else {
            console.warn('Forecast unavailable. Showing current weather only.');
          }
        }
        
        let forecastData = null;
        if (forecastResponse && forecastResponse.ok) {
          forecastData = await forecastResponse.json();
        }
        
        if (!forecastData || !forecastData.list || forecastData.list.length === 0) {
          console.warn('Forecast data incomplete, showing current weather only.');
        }

        displayCurrentWeather(currentData);
        
        if (forecastData && forecastData.list && forecastData.list.length > 0) {
          displayForecast(forecastData);
        } else {
          const forecastCards = document.getElementById('forecastCards');
          forecastCards.innerHTML = '<div class="col-12"><div class="alert alert-info"><i class="fas fa-info-circle"></i> Forecast data temporarily unavailable. Current weather is up to date.</div></div>';
        }
        
        if (SHOW_PAST_DAYS) {
          displayPastDays(forecastData, currentData);
        }

        const updateTime = new Date();
        document.getElementById('lastUpdate').textContent = 'Last updated: ' + updateTime.toLocaleTimeString() + ' (' + updateTime.toLocaleDateString() + ')';
        
        errorEl.style.display = 'none';
        
        loadingEl.style.display = 'none';
        currentWeatherEl.style.display = 'block';
        isDemoMode = false;
        
        console.log('✅ Weather data fetched successfully for:', location);
        console.log('Temperature:', currentData.main.temp, '°C');
        
      } catch (error) {
        loadingEl.style.display = 'none';
        errorEl.style.display = 'block';
        
        let errorMessage = error.message;
        console.error('❌ Weather API Error:', {
          message: error.message,
          name: error.name,
          location: location,
          apiKeyConfigured: isApiKeyValid()
        });
        
        if (error.message.includes('API key') || error.message.includes('401')) {
          errorMessage = '⚠ Invalid API Key: ' + error.message + '<br><small>Please configure your API key in <code>weather_config.php</code>. Get your free key at <a href="https://openweathermap.org/api" target="_blank">openweathermap.org</a></small>';
        } else if (error.message.includes('404') || error.message.includes('not found')) {
          errorMessage = '❌ Location not found: "' + location + '"<br><small>Try: Mumbai, Delhi, Bangalore, Pune, or city name with country (e.g., "Mumbai, IN")</small>';
        } else if (error.message.includes('429') || error.message.includes('Too many')) {
          errorMessage = '⏱ Rate limit exceeded: ' + error.message + '<br><small>Please wait a few minutes and try again.</small>';
        } else if (error.message.includes('timeout') || error.message.includes('Network')) {
          errorMessage = '🌐 Connection issue: ' + error.message + '<br><small>Please check your internet connection and try again.</small>';
        } else {
          errorMessage = '⚠ Error: ' + error.message + '<br><small>Check browser console (F12) for more details.</small>';
        }
        
        errorEl.innerHTML = errorMessage;
        errorEl.className = 'alert alert-danger';
        
      } finally {
        refreshBtn.classList.remove('loading');
        refreshBtn.disabled = false;
      }
    }

    function displayCurrentWeather(data) {
      if (!data || !data.main || !data.weather || !data.weather[0]) {
        console.error('Invalid weather data:', data);
        return;
      }
      
      const locationName = data.name || 'Unknown';
      const country = (data.sys && data.sys.country) ? data.sys.country : '';
      const temp = data.main.temp ? Math.round(data.main.temp) : '--';
      const feelsLike = data.main.feels_like ? Math.round(data.main.feels_like) : '--';
      const humidity = data.main.humidity || '--';
      const pressure = data.main.pressure || '--';
      const windSpeed = data.wind && data.wind.speed ? Math.round(data.wind.speed * 3.6) : '--';
      const visibility = data.visibility ? (data.visibility / 1000).toFixed(1) : '--';
      const description = data.weather[0].description ? 
        data.weather[0].description.charAt(0).toUpperCase() + data.weather[0].description.slice(1) : 'N/A';
      const iconCode = data.weather[0].icon || '01d';
      
      document.getElementById('currentLocation').textContent = country ? locationName + ', ' + country : locationName;
      document.getElementById('currentTemp').textContent = temp + '°';
      document.getElementById('currentDesc').textContent = description;
      document.getElementById('currentFeelsLike').textContent = feelsLike;
      document.getElementById('currentHumidity').textContent = humidity + '%';
      document.getElementById('currentWind').textContent = windSpeed + ' km/h';
      document.getElementById('currentVisibility').textContent = visibility + ' km';
      document.getElementById('currentPressure').textContent = pressure + ' hPa';
      
      const iconEl = document.getElementById('currentIcon');
      iconEl.className = 'fas ' + getWeatherIconClass(iconCode);
      
      const currentCard = document.querySelector('.weather-current-card');
      const weatherMain = data.weather[0].main ? data.weather[0].main.toLowerCase() : 'clear';
      
      let gradientStart, gradientEnd;
      
      if (weatherMain.includes('rain') || weatherMain.includes('drizzle')) {
        gradientStart = WEATHER_COLORS.rain.start;
        gradientEnd = WEATHER_COLORS.rain.end;
      } else if (weatherMain.includes('cloud')) {
        gradientStart = WEATHER_COLORS.cloudy.start;
        gradientEnd = WEATHER_COLORS.cloudy.end;
      } else if (weatherMain.includes('sun') || weatherMain.includes('clear')) {
        gradientStart = WEATHER_COLORS.sunny.start;
        gradientEnd = WEATHER_COLORS.sunny.end;
      } else if (weatherMain.includes('storm') || weatherMain.includes('thunder')) {
        gradientStart = WEATHER_COLORS.storm.start;
        gradientEnd = WEATHER_COLORS.storm.end;
      } else if (weatherMain.includes('snow')) {
        gradientStart = WEATHER_COLORS.snow.start;
        gradientEnd = WEATHER_COLORS.snow.end;
      } else {
        gradientStart = WEATHER_COLORS.default.start;
        gradientEnd = WEATHER_COLORS.default.end;
      }
      
      currentCard.style.background = `linear-gradient(135deg, ${gradientStart} 0%, ${gradientEnd} 100%)`;
      
      console.log('Weather displayed for:', locationName, '| Temp:', temp + '°C', '| Condition:', description);
    }

    function displayForecast(data) {
      const forecastCards = document.getElementById('forecastCards');
      forecastCards.innerHTML = '';
      
      if (!data || !data.list || data.list.length === 0) {
        forecastCards.innerHTML = '<div class="col-12"><p class="text-muted text-center">Forecast data not available.</p></div>';
        return;
      }
      
      const dailyForecast = {};
      const today = new Date();
      today.setHours(0, 0, 0, 0);
      
      data.list.forEach(item => {
        const date = new Date(item.dt * 1000);
        date.setHours(0, 0, 0, 0);
        const dayKey = date.toDateString();
        
        if (date >= today) {
          if (!dailyForecast[dayKey]) {
            dailyForecast[dayKey] = item;
          }
        }
      });

      const forecastEntries = Object.entries(dailyForecast).slice(0, 5);
      
      if (forecastEntries.length === 0) {
        forecastCards.innerHTML = '<div class="col-12"><p class="text-muted text-center">Forecast data not available.</p></div>';
        return;
      }
      forecastEntries.forEach(([dayKey, item]) => {
        if (!item || !item.main || !item.weather || !item.weather[0]) return;
        
        const date = new Date(dayKey);
        const dayName = date.toLocaleDateString('en-US', { weekday: 'short' });
        const monthDay = date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        
        const temp = item.main.temp ? Math.round(item.main.temp) : '--';
        const humidity = item.main.humidity || '--';
        const windSpeed = item.wind && item.wind.speed ? Math.round(item.wind.speed * 3.6) : '--';
        const description = item.weather[0].description ? item.weather[0].description.charAt(0).toUpperCase() + item.weather[0].description.slice(1) : 'N/A';
        const icon = getWeatherIconClass(item.weather[0].icon || '01d');
        
        const card = document.createElement('div');
        card.className = 'col-md-6 col-lg-2 col-sm-6';
        card.innerHTML = `
          <div class="card border-0 shadow-sm h-100 forecast-card">
            <div class="card-body text-center">
              <div class="fw-bold text-success">${dayName}</div>
              <div class="small text-muted mb-2">${monthDay}</div>
              <i class="fas ${icon} fa-2x mb-2 text-info"></i>
              <div class="h5 mb-1">${temp}°C</div>
              <div class="small text-muted">${description}</div>
              <div class="mt-2 pt-2 border-top">
                <div class="row g-1 small">
                  <div class="col-6"><i class="fas fa-tint text-info"></i> ${humidity}%</div>
                  <div class="col-6"><i class="fas fa-wind text-secondary"></i> ${windSpeed}</div>
                </div>
              </div>
            </div>
          </div>
        `;
        forecastCards.appendChild(card);
      });
    }

    function displayPastDays(forecastData, currentData) {
      const pastCards = document.getElementById('pastWeatherCards');
      pastCards.innerHTML = '';
      
      if (!currentData || !currentData.main) {
        pastCards.innerHTML = '<div class="col-12"><p class="text-muted text-center">Past weather data not available.</p></div>';
        return;
      }
      
      const simulatedPastDays = [
        {
          date: new Date(Date.now() - 86400000),
          temp: (currentData.main.temp || 28) - (Math.random() * 2 - 1),
          desc: 'Partly cloudy',
          humidity: (currentData.main.humidity || 65) - (Math.random() * 5),
          icon: 'cloud-sun'
        },
        {
          date: new Date(Date.now() - 172800000),
          temp: (currentData.main.temp || 28) - (Math.random() * 2),
          desc: 'Sunny',
          humidity: (currentData.main.humidity || 65) - (Math.random() * 3),
          icon: 'sun'
        },
        {
          date: new Date(Date.now() - 259200000),
          temp: (currentData.main.temp || 28) - (Math.random() * 3),
          desc: 'Cloudy',
          humidity: (currentData.main.humidity || 65) + (Math.random() * 5),
          icon: 'cloud'
        }
      ];

      simulatedPastDays.forEach((day, index) => {
        const date = day.date;
        const dayName = date.toLocaleDateString('en-US', { weekday: 'short' });
        const monthDay = date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        
        const card = document.createElement('div');
        card.className = 'col-md-4 col-sm-6';
        card.innerHTML = `
          <div class="card border-0 shadow-sm h-100 past-weather-card" style="background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);">
            <div class="card-body text-center">
              <div class="fw-bold text-secondary">${dayName}</div>
              <div class="small text-muted mb-2">${monthDay}</div>
              <i class="fas fa-${day.icon} fa-2x mb-2" style="color: #6c757d;"></i>
              <div class="h5 mb-1">${Math.round(day.temp)}°C</div>
              <div class="small text-muted">${day.desc}</div>
              <div class="mt-2 pt-2 border-top">
                <div class="small"><i class="fas fa-tint text-info"></i> Humidity: ${day.humidity}%</div>
              </div>
            </div>
          </div>
        `;
        pastCards.appendChild(card);
      });
    }

    function getWeatherIconClass(iconCode) {
      const iconMap = {
        '01d': 'fa-sun', '01n': 'fa-moon',
        '02d': 'fa-cloud-sun', '02n': 'fa-cloud-moon',
        '03d': 'fa-cloud', '03n': 'fa-cloud',
        '04d': 'fa-cloud', '04n': 'fa-cloud',
        '09d': 'fa-cloud-rain', '09n': 'fa-cloud-rain',
        '10d': 'fa-cloud-sun-rain', '10n': 'fa-cloud-moon-rain',
        '11d': 'fa-bolt', '11n': 'fa-bolt',
        '13d': 'fa-snowflake', '13n': 'fa-snowflake',
        '50d': 'fa-smog', '50n': 'fa-smog'
      };
      return iconMap[iconCode] || 'fa-cloud';
    }

    function showWeatherError(message) {
      const errorEl = document.getElementById('weatherError');
      errorEl.style.display = 'block';
      errorEl.innerHTML = message;
      errorEl.className = 'alert alert-warning';
    }
  </script>
  
  <!-- Farmer Profile Modal -->
  <div class="modal fade" id="farmerProfileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-user-circle text-success"></i> Your Profile</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label text-muted">Username</label>
            <div class="form-control"><?php echo esc($farmerInfo['username'] ?? ''); ?></div>
          </div>
          <div class="mb-3">
            <label class="form-label text-muted">Email</label>
            <div class="form-control"><?php echo esc($farmerInfo['email'] ?? ''); ?></div>
          </div>
          <div class="mb-3">
            <label class="form-label text-muted">Mobile Number</label>
            <div class="form-control"><?php echo esc($farmerInfo['mobile_number'] ?? 'Not set'); ?></div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>
  

  <style>
/* --- Sticky Footer Fix --- */
html, body {
  height: 100%;
  margin: 0;
  padding: 0;
}



.main-container {
  flex: 1;
}

/* --- Your existing footer styling --- */
footer.footer {
  background: linear-gradient(135deg, #2e7d32 0%, #1b5e20 100%);
  padding: 20px 0;
  text-align: center;
  width: 100%;
}
footer.footer .content {
  margin: 0 auto;
}
footer.footer .bottom {
  font-size: 14px;
}
footer.footer .bottom p {
  margin: 0;
  color: #fff;
}
</style>

  <!-- Footer -->
  <footer class="footer">
    <div class="content">
      <div class="bottom">
        <p>&copy; 2025 AgriFarm. All rights reserved.</p>
      </div>
    </div>
  </footer>
</body>
</html>

