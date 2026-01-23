# Weather Monitoring System Setup Guide

## Overview
The farmer dashboard now includes a beautiful live weather monitoring system that provides:
- **Current Weather**: Real-time temperature, humidity, wind speed, visibility, and pressure
- **Past 3 Days**: Historical weather data for reference
- **5-Day Forecast**: Future weather predictions to help with farming planning

## Quick Setup (5 Minutes)

### Step 1: Get Your Free OpenWeatherMap API Key

1. Visit [OpenWeatherMap API](https://openweathermap.org/api)
2. Click on **"Sign Up"** to create a free account (no credit card required)
3. After signing up, wait 2-10 minutes for account activation
4. Go to the **"API Keys"** section in your account dashboard
5. Copy your API key (it looks like: `abc123def456ghi789jkl012mno345pqr`)
   - **Note**: New API keys may take 2-10 minutes to activate

### Step 2: Configure the API Key

1. Open `farmer_home.php` in your code editor
2. Find this line around line 412:
   ```javascript
   const WEATHER_API_KEY = '00f0e94087dd620dc14003cc1b000de1';
   ```
3. Replace `'00f0e94087dd620dc14003cc1b000de1'` with your actual API key:
   ```javascript
   const WEATHER_API_KEY = 'your-actual-api-key-here';
   ```
4. Save the file

### Step 3: Test the Weather System

1. Open your farmer dashboard in a browser (login as a farmer)
2. Scroll down to the **"Live Weather Monitoring"** section
3. The weather should automatically load for the default location (Mumbai)
4. Enter a city name (e.g., "Delhi", "Pune", "Bangalore", "Chennai") and click **"Get Weather"**
5. You should see live weather data!

## Features

### ✨ Current Weather Display
- Large temperature display with weather icon
- Weather description (e.g., "Partly cloudy", "Clear sky")
- "Feels like" temperature
- Humidity percentage
- Wind speed (km/h)
- Visibility (km)
- Atmospheric pressure (hPa)
- **Dynamic gradient background** that changes based on weather conditions:
  - 🌧 **Rain/Drizzle**: Blue gradient (#4facfe to #00f2fe)
  - ☁️ **Cloudy**: Purple gradient (#667eea to #764ba2)
  - ☀️ **Sunny/Clear**: Pink gradient (#f093fb to #f5576c)
  - ⛈ **Storm/Thunder**: Dark gradient (#434343 to #000000)
  - ❄️ **Snow**: White gradient (#e0e0e0 to #ffffff)

### 📅 Past Days Weather
- Shows weather data for the past 3 days
- Helps farmers track weather patterns
- Displays temperature, description, and humidity
- **Note**: Currently uses simulated data (real historical API requires paid subscription)

### 🔮 5-Day Forecast
- Daily weather predictions for next 5 days
- Temperature highs/lows
- Weather conditions with icons
- Humidity and wind information
- Perfect for planning farming activities
- Helps schedule:
  - Planting and harvesting
  - Irrigation
  - Pesticide application
  - Crop protection

### 🔄 Auto-Refresh
- Automatically refreshes every 30 minutes
- Manual refresh button available
- Shows last update time
- Keeps data current throughout the day

### 📍 Location Search
- Search weather for any city worldwide
- Default location: Mumbai (can be changed)
- Real-time location updates
- Popular Indian cities: Mumbai, Delhi, Bangalore, Pune, Chennai, Kolkata, Hyderabad, Ahmedabad, Jaipur, Surat
- International cities: New York, London, Tokyo, etc.

## API Limits (Free Tier)

The free OpenWeatherMap API includes:
- ✅ **60 calls per minute**
- ✅ **1,000,000 calls per month**
- ✅ Current weather data
- ✅ 5-day/3-hour forecast
- ✅ More than sufficient for farming applications
- ✅ No credit card required

## Configuration Options

### Change Default Location

In `farmer_home.php`, find line ~248 and change:
```html
<input type="text" class="form-control" id="weatherLocation" ... value="Mumbai">
```
Change `value="Mumbai"` to your preferred city, e.g., `value="Delhi"`

### Change Auto-Refresh Interval

In `farmer_home.php`, find line ~450 and change:
```javascript
}, 1800000); // 30 minutes
```

Options:
- **10 minutes**: `600000`
- **30 minutes**: `1800000` (default)
- **1 hour**: `3600000`
- **2 hours**: `7200000`

### Number of Forecast Days

In `farmer_home.php`, find the `displayForecast` function around line ~776:
```javascript
const forecastEntries = Object.entries(dailyForecast).slice(0, 5);
```
Change `slice(0, 5)` to `slice(0, 3)` for 3 days, `slice(0, 7)` for 7 days, etc.

## Troubleshooting

### Error: "API key not configured"
- ✅ Make sure you've replaced the placeholder API key in `farmer_home.php` line 412
- ✅ Check that there are no extra spaces or quotes around the API key
- ✅ Verify the API key is at least 20 characters long
- ✅ Ensure the API key is wrapped in quotes: `'your-api-key-here'`

### Error: "Invalid API key" or "401 Unauthorized"
- ✅ **Wait 2-10 minutes** - new API keys take time to activate
- ✅ Verify your API key is correct (copy-paste to avoid typos)
- ✅ Check your OpenWeatherMap account is activated
- ✅ Ensure you haven't exceeded your API quota (check your dashboard)
- ✅ Try regenerating your API key if it still doesn't work

### Error: "Location not found" or "404"
- ✅ Make sure the city name is spelled correctly
- ✅ Try using city name with country (e.g., "Mumbai, IN", "Delhi, IN")
- ✅ Use English city names (not local language names)
- ✅ Check city exists in OpenWeatherMap database
- ✅ Try major cities first to verify API is working

### Weather not loading
- ✅ Check your internet connection
- ✅ Verify your API key is correct in `farmer_home.php`
- ✅ Check browser console (F12) for error messages
- ✅ Ensure OpenWeatherMap service is available
- ✅ Try a different city name to isolate the issue
- ✅ Clear browser cache and reload

### Weather data incomplete
- ✅ Free API tier provides limited forecast data (3-hour intervals)
- ✅ Some advanced features require paid subscription
- ✅ Current weather should always work on free tier
- ✅ Forecast may show fewer days on free tier

### CORS Errors
- ✅ OpenWeatherMap API supports CORS by default
- ✅ If you see CORS errors, check browser console
- ✅ Ensure you're not using a proxy that blocks API calls
- ✅ Try from a different network if issue persists

## API Documentation

For more detailed information:
- **OpenWeatherMap API Docs**: [https://openweathermap.org/api](https://openweathermap.org/api)
- **Current Weather API**: [https://openweathermap.org/current](https://openweathermap.org/current)
- **5-Day Forecast API**: [https://openweathermap.org/forecast5](https://openweathermap.org/forecast5)
- **API Key Management**: [https://home.openweathermap.org/api_keys](https://home.openweathermap.org/api_keys)

## Advanced Usage

### Using GPS Coordinates

Instead of city name, you can use coordinates:
```javascript
// Modify fetchWeatherData function
// Instead of: q=${encodeURIComponent(location)}
// Use: lat=${latitude}&lon=${longitude}

// Example:
const weatherUrl = `${WEATHER_API_URL}/weather?lat=19.0760&lon=72.8777&appid=${WEATHER_API_KEY}&units=metric`;
```

### Storing Last Location (LocalStorage)

To remember the last searched location:
```javascript
// In fetchWeatherData function, after successful fetch:
localStorage.setItem('lastWeatherLocation', location);

// In DOMContentLoaded, load saved location:
const lastLocation = localStorage.getItem('lastWeatherLocation');
if (lastLocation) {
  document.getElementById('weatherLocation').value = lastLocation;
  fetchWeatherData(); // Fetch weather for saved location
}
```

### Weather-Based Farming Recommendations

Add farming tips based on weather:
```javascript
function getFarmingTips(weatherData) {
  const temp = weatherData.main.temp;
  const humidity = weatherData.main.humidity;
  const condition = weatherData.weather[0].main.toLowerCase();
  
  let tips = [];
  
  if (condition.includes('rain')) {
    tips.push('🌧 Good time for irrigation - natural watering');
    tips.push('💧 Consider reducing manual watering');
    tips.push('⚠️ Postpone pesticide application');
  } else if (temp > 35) {
    tips.push('☀️ High temperature - increase watering frequency');
    tips.push('🌿 Provide shade for sensitive crops');
    tips.push('💧 Early morning or evening watering recommended');
  } else if (humidity > 80) {
    tips.push('💨 High humidity - watch for fungal diseases');
    tips.push('🌾 Good ventilation important');
  }
  
  return tips;
}
```

## Security Best Practices

1. **Never commit API keys to public repositories**
   - Add `farmer_home.php` to `.gitignore` if it contains API keys
   - Use environment variables for production
   - Or use a separate config file excluded from version control

2. **Monitor API Usage**
   - Check your OpenWeatherMap dashboard regularly
   - Set up email alerts for quota limits
   - Consider caching weather data to reduce API calls

3. **Rate Limiting**
   - Free tier: 60 calls/minute
   - Implement client-side caching for 10-15 minutes
   - Don't auto-refresh too frequently

## Support

For issues or questions:
- **OpenWeatherMap Support**: [https://openweathermap.org/help](https://openweathermap.org/help)
- **Browser Console**: Press F12 to check for JavaScript errors
- **API Status**: Check [https://status.openweathermap.org](https://status.openweathermap.org)
- **Community Forum**: [https://openweathermap.org/forum](https://openweathermap.org/forum)

## FAQ

**Q: Is the API free?**  
A: Yes! The free tier provides 1 million calls per month, which is more than enough for farming use.

**Q: How accurate is the weather data?**  
A: OpenWeatherMap provides highly accurate weather data updated every 10-15 minutes from official weather stations.

**Q: Can I use this offline?**  
A: No, the system requires an active internet connection to fetch live weather data.

**Q: Will this work on mobile?**  
A: Yes! The weather system is fully responsive and works on all devices.

**Q: Can I track weather for multiple locations?**  
A: Yes! You can search for any city, but only one location is displayed at a time.

**Q: How often should I refresh?**  
A: The default 30-minute auto-refresh is ideal. Manual refresh is available anytime.

**Q: Can I get historical weather?**  
A: Past 3 days are simulated. For real historical data, you'd need the paid Historical Weather API subscription.

---

Enjoy your weather monitoring system! 🌾☀🌧

**Need help?** Check the troubleshooting section above or visit OpenWeatherMap support.