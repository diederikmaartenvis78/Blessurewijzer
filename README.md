# Bracefox Blessurewijzer 2.0

AI-powered conversational assistant for injury advice and product recommendations on the Bracefox webshop.

## 🎯 Features

- **AI-Powered Chat**: Intelligent conversational interface using OpenAI GPT models
- **Smart Product Recommendations**: Context-aware product matching based on user complaints
- **Free Health Advice**: Exercises, thermal advice, rest guidance, and lifestyle tips
- **Medical Disclaimer**: Prominent, legally compliant warnings
- **Premium UI/UX**: Beautiful, responsive design with smooth animations
- **Analytics Dashboard**: Track conversations, conversions, and popular products
- **Performance Optimized**: Smart caching, token optimization, and rate limiting
- **Security First**: CSRF protection, input sanitization, and rate limiting

## 📋 Requirements

- WordPress 5.8+
- PHP 7.4+
- WooCommerce 5.0+
- OpenAI API key

## 🚀 Installation

1. Upload the plugin folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to **Blessurewijzer > Settings**
4. Enter your OpenAI API key
5. Configure settings as needed
6. Add the shortcode `[blessurewijzer]` to any page

## 🔧 Configuration

### API Settings

- **API Key**: Your OpenAI API key from platform.openai.com
- **Model**: Choose between GPT-4o Mini (recommended), GPT-4o, or GPT-3.5 Turbo
- **Temperature**: Controls AI creativity (0-2, default 0.7)
- **Max Tokens**: Maximum response length (default 1500)
- **Timeout**: API request timeout in seconds (default 30)

### Performance Settings

- **Cache TTL**: How long to cache product/blog data (default 3600 seconds)

### Security Settings

- **Rate Limit Max**: Maximum requests per IP (default 10)
- **Rate Limit Window**: Time window for rate limiting (default 60 seconds)

## 📝 Shortcode Usage

Basic usage:
```
[blessurewijzer]
```

With custom title:
```
[blessurewijzer title="Stel je vraag"]
```

With custom placeholder:
```
[blessurewijzer placeholder="Beschrijf je blessure..."]
```

## 🏗️ Architecture

### Plugin Structure
```
bracefox-blessurewijzer/
├── includes/
│   ├── admin/          # Admin functionality
│   ├── api/            # OpenAI client & AJAX handler
│   ├── frontend/       # Shortcode & assets
│   ├── repositories/   # Data access layer
│   └── services/       # Business logic
├── assets/
│   ├── css/            # Stylesheets
│   └── js/             # JavaScript
├── templates/          # PHP templates
│   ├── admin/
│   └── frontend/
└── languages/          # Translations
```

### Database Tables

- `wp_blessurewijzer_sessions`: Chat sessions
- `wp_blessurewijzer_messages`: Conversation messages
- `wp_blessurewijzer_recommendations`: Product recommendations

## 🎨 Design System

The plugin implements a comprehensive design system based on the PRD:

### Colors
- Primary: `#f97316` (Bracefox Orange)
- Grays: `#f8fafc` to `#0f172a`
- Semantic: Success, Warning, Error, Info

### Typography
- Font Family: Inter, system fonts
- Sizes: 12px - 30px
- Weights: 400, 500, 600, 700

### Animations
- Message slide-in: 300ms
- Card reveal: 400ms
- Button interactions: 150ms
- Loading states: Progressive disclosure

## 📊 Analytics

The analytics dashboard provides insights into:

- Total conversations
- Conversion rate (advice → product click)
- Completed/abandoned sessions
- Average messages per session
- Most recommended products
- Common complaint keywords (word cloud)

## 🔒 Security Features

- **CSRF Protection**: WordPress nonces on all AJAX requests
- **Input Sanitization**: All user input is sanitized
- **Output Escaping**: All output is properly escaped
- **Rate Limiting**: IP-based request limiting
- **SQL Injection Prevention**: Prepared statements
- **XSS Prevention**: Content Security Policy

## 🚀 Performance Optimizations

- **Smart Caching**: Product and blog data cached with TTL
- **Token Optimization**: Context-based product filtering (40-60% reduction)
- **Lazy Loading**: Assets only load when shortcode is present
- **Database Indexing**: Optimized queries with proper indexes
- **Progressive Loading**: Premium animation for long-running requests

## 🧪 Testing

### Test API Connection
1. Go to **Blessurewijzer > Settings**
2. Click "Test API Connection"
3. Verify successful connection

### Test Chat Functionality
1. Add `[blessurewijzer]` to a test page
2. Visit the page
3. Ask a test question: "Ik heb last van mijn knie"
4. Verify AI response and product recommendation

## 🐛 Troubleshooting

### No AI Response
- Check API key is configured
- Verify API key is valid
- Check error logs in WordPress

### Products Not Showing
- Ensure WooCommerce is active
- Verify products are published
- Clear cache: Settings > Clear All Caches

### Rate Limited
- Increase rate limit in settings
- Wait for time window to expire
- Check if user IP is correct

## 📚 Development

### Adding New Features
1. Follow WordPress coding standards
2. Use proper sanitization/escaping
3. Add to appropriate layer (admin/api/frontend)
4. Update documentation

### Customizing Prompts
Edit `includes/services/class-prompt-builder.php` to modify:
- Role definition
- Strict rules
- Output format
- Matching guidelines

### Styling Customizations
Override CSS variables in your theme:
```css
.bw-widget {
  --primary-500: #your-color;
  --font-primary: 'Your Font', sans-serif;
}
```

## 🌍 Translations

The plugin is translation-ready. Translation files:
- POT file: `languages/bracefox-blessurewijzer.pot`
- Dutch (nl_NL): Included

## 📄 License

GPL v2 or later

## 👥 Support

- Documentation: Check this README
- Issues: Report bugs with details
- Feature Requests: Submit with use case

## 🎯 Roadmap

Future enhancements (v2.1+):
- Multi-language support (EN, DE)
- Voice input
- Image upload
- User accounts with saved history
- A/B testing framework
- WhatsApp/Messenger integration

## ⚖️ Legal Notice

This tool provides general advice only. Always consult medical professionals for health concerns. Bracefox is not liable for any consequences from following the advice provided.

---

**Version**: 2.0.0
**Author**: Bracefox
**Website**: https://bracefox.nl