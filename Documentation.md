# APOD AI Analysis Plugin Documentation

## Overview
The APOD AI Analysis plugin integrates NASA's Astronomy Picture of the Day (APOD) with Google's Vertex AI to provide intelligent analysis of astronomical images. The plugin fetches daily astronomy pictures, analyzes them using AI, and presents them in an interactive gallery with detailed explanations.

## Features
- AI-powered image analysis using Google Vertex AI
- Keyword and content analysis
- Similar image recommendations
- Searchable image gallery
- Responsive design
- Caching system for optimal performance

## Installation

### Prerequisites
- WordPress 5.8 or higher
- PHP 7.4 or higher
- Node.js and npm for development
- Google Cloud account with Vertex AI enabled
- NASA API key

### Setup Steps

1. **Plugin Installation**
   ```bash
   # Navigate to WordPress plugins directory
   cd wp-content/plugins
   
   # Clone or copy the plugin files
   git clone [repository-url] apod-ai-analysis
   
   # Navigate to plugin directory
   cd apod-ai-analysis
   
   # Install dependencies
   npm install
   
   # Build the plugin
   npm run build
   ```

2. **WordPress Configuration**
   - Activate the plugin in WordPress admin panel
   - Go to Settings > APOD AI
   - Enter your API credentials:
     - NASA API Key
     - Vertex AI API Key
     - Google Cloud Project ID
     - Vertex AI Endpoint ID

3. **Usage**
   - Add the shortcode `[apod_ai_analysis]` to any page or post
   - The plugin will display the APOD gallery with AI analysis

## Directory Structure
```
apod-ai-analysis/
├── src/                          # React source files
│   ├── components/
│   │   ├── VertexAnalysis.js
│   │   ├── KeywordAnalysis.js
│   │   ├── SimilarImages.js
│   │   └── index.js
│   └── app.js
├── assets/                       # Compiled files
│   ├── css/
│   │   └── apod-analysis.css
│   └── js/
│       └── apod-analysis.js
├── includes/                     # PHP classes
│   ├── class-vertex-api.php
│   └── class-content-analyzer.php
├── package.json
├── webpack.config.js
└── apod-ai-analysis.php
```

## Configuration Options

### NASA API
- Get your API key from: https://api.nasa.gov/

### Google Vertex AI
- Requires Google Cloud account
- Enable Vertex AI API in Google Cloud Console
- Create a service account with appropriate permissions
- Set up an endpoint for text generation

### Cache Settings
- Default cache duration: 24 hours
- Configurable in plugin settings
- Automatic cache cleanup daily

## Features in Detail

### AI Analysis
The plugin provides three levels of analysis:
1. **Basic Analysis**
   - General explanation
   - Main subject identification
   - Key features

2. **Content Analysis**
   - Keyword extraction
   - Technical term identification
   - Measurements and data

3. **Similar Images**
   - Content-based similarity matching
   - Match percentage calculation
   - Quick navigation to related images

### Search and Filtering
- Real-time search functionality
- Filter by date
- Category-based filtering
- Keyword-based search

### Responsive Design
- Mobile-friendly layout
- Adaptive image sizing
- Touch-friendly controls
- Flexible grid system

## Development

### Building from Source
```bash
# Development build with watch
npm run dev

# Production build
npm run build
```

### Adding New Features
1. Create new React components in `src/components/`
2. Update `app.js` to include new components
3. Add required PHP functions in relevant class files
4. Update CSS as needed

### WordPress Integration
The plugin uses:
- WordPress REST API
- WP Ajax
- React integration
- WordPress transients for caching

## Troubleshooting

### Common Issues

1. **Images Not Loading**
   - Check NASA API key validity
   - Verify API rate limits
   - Check server error logs

2. **AI Analysis Not Working**
   - Verify Vertex AI credentials
   - Check Google Cloud API quota
   - Ensure endpoint is configured correctly

3. **Performance Issues**
   - Check cache settings
   - Verify server resources
   - Monitor API response times

### Debug Mode
Enable WordPress debug mode in wp-config.php:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Support and Updates

### Getting Help
- Submit issues on GitHub
- Check WordPress support forums
- Contact plugin author

### Updates
- Plugin checks for updates automatically
- Manual updates available through WordPress admin
- Check changelog for latest changes

## Security Considerations
- API keys stored securely in WordPress options
- AJAX nonce verification
- Input sanitization
- Output escaping
- Rate limiting
- Access control


## Credits
- NASA APOD API
- Google Vertex AI
- WordPress
- React
- Contributors and maintainers
