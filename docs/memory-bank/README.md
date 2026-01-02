# Memory Bank - Musique Approximative

## Project Overview

**Project Name**: Musique Approximative  
**Repository**: `constructions-incongrues/net.musiqueapproximative.www`  
**Type**: Music sharing platform / Daily playlist website  
**Description**: An anarchic outlet for a band of cracked music lovers. A daily playlist fed by the obsessions and discoveries of each contributor.

## Technology Stack

### Backend
- **Framework**: Symfony 1.5 (legacy PHP framework)
- **PHP Version**: 7.4+
- **ORM**: Doctrine 1.3
- **Database**: MySQL (accessed via Adminer)
- **Template Engine**: PHP templates

### Frontend
- **JavaScript**: jQuery (via sfJqueryReloadedPlugin)
- **Audio Player**: JW Player (SWF-based, located in `src/web/swf/mediaplayer-5.9/`)
- **Styling**: Custom CSS with theme support

### Infrastructure
- **Containerization**: Docker + Docker Compose
- **Web Server**: Nginx (proxy on port 8080)
- **PHP Server**: Built-in PHP server (port 8001)
- **Development Domain**: `www.musiqueapproximative.test`

### Key Dependencies
```json
{
  "lexpress/doctrine1": "1.3.*",
  "lexpress/symfony1": "1.5.*",
  "nyholm/psr7": "^1.8",
  "php": "^7.4",
  "symfony/process": "^3.2"
}
```

## Project Structure

```
musiqueapproximative/
├── .devcontainer/          # Dev container configuration
├── .github/                # GitHub workflows
├── .vscode/                # VS Code settings
├── docker-compose.yml      # Docker orchestration
├── Dockerfile              # PHP container definition
├── nginx.conf              # Nginx configuration
├── start-dev.sh            # Development startup script
├── stop-dev.sh             # Development shutdown script
├── src/                    # Main application code
│   ├── apps/
│   │   └── frontend/       # Frontend application
│   │       ├── modules/
│   │       │   └── post/   # Post module (main feature)
│   │       ├── templates/  # Global templates
│   │       └── config/     # App configuration
│   ├── lib/                # Custom libraries
│   ├── plugins/            # Symfony plugins
│   ├── web/                # Public web root
│   │   ├── desastres/      # "Disaster" effects system
│   │   ├── images/         # Static images
│   │   ├── theme/          # Theme assets
│   │   └── tracks/         # Audio files
│   ├── data/               # Data files & fixtures
│   └── config/             # Global configuration
├── etc/                    # Additional configuration
└── var/                    # Variable data
```

## Core Features

### 1. Post System
- **Main Entity**: `Post` (Doctrine model)
- **Key Fields**:
  - `track_author` - Artist name
  - `track_title` - Track title
  - `track_filename` - Audio file name
  - `body` - Post description (Markdown)
  - `slug` - URL-friendly identifier
  - `publish_on` - Publication timestamp
  - Contributor relationship (sfGuardUser)

### 2. Post Actions (`postActions`)
Located: `src/apps/frontend/modules/post/actions/actions.class.php`

**Key Methods**:
- `executeShow()` - Display single post (line 27)
- `executeList()` - List posts with search/filter (line 153)
- `executeFeed()` - RSS feed generation (line 186)
- `executeRandom()` - Random post (line 254)
- `executeNext()` / `executePrev()` - Navigation (lines 266, 272)
- `executeOembed()` - oEmbed support (line 289)
- `executeHome()` - Homepage redirect to latest (line 141)

### 3. Glitch Logo Feature
**Purpose**: Randomly glitch the site logo for visual variety

**Implementation**:
- **Service**: `https://gliche.constructions-incongrues.net/`
- **Trigger**: Random (1 in N chance, configurable via `app_glitch_divisor`)
- **Usage Locations**:
  - Post display page (line 60-66)
  - RSS feed items (line 226-231)
  - Background images on post pages
- **Parameters**:
  - `seed` - Post ID for reproducibility
  - `amount` - Glitch intensity (0-100)
  - `url` - Source image URL

**Example**:
```php
$urlImg = sprintf(
  'https://gliche.constructions-incongrues.net/glitch?seed=%d&amount=%d&url=%s/images/logo_500.png',
  $post->id,
  rand(0, 100),
  $request->getUriPrefix()
);
```

### 4. Desastre System
**Purpose**: Apply random visual/behavioral "disasters" to pages

**Configuration**: `src/apps/frontend/config/desastres.yml`  
**Recipes Location**: `src/web/desastres/`

**Available Disasters**:
- `amour` - Love-themed effects
- `bleu` - Blue color scheme
- `fish` - Fish-related visuals
- `light` - Light effects
- `mamie` - Grandma theme
- `mangelettres` - Letter eating
- `musique` - Music-related
- `noir` - Black/dark theme
- `postillons` - Spit effects
- `redirect` - Redirects
- `robot` - Robot theme
- `sale` - Dirty/messy effects
- `splitouine` - Split effects
- `tts` - Text-to-speech

**Helper**: `apply_desastre()` function (loaded via `Desastre` helper)

### 5. Multi-Format Support
**Supported Formats**:
- `json` - JSON API (jsonapi.org compliant)
- `max` - Max/MSP format
- `xspf` - XSPF playlist format
- `rss` - RSS 2.01 feed
- `oembed` - oEmbed (JSON/XML)

### 6. OpenGraph Metadata
Full OpenGraph support for social sharing:
- Title, description, image
- Video player embed (SWF)
- Proper dimensions (476x476)

## Development Workflow

### Starting Development
```bash
./start-dev.sh
```

### Access Points
- **Direct PHP**: http://localhost:8001
- **Via Nginx**: http://localhost:8080
- **Domain**: http://www.musiqueapproximative.test:8080
- **Frontend Dev**: http://www.musiqueapproximative.test/frontend_dev.php
- **Admin**: http://www.musiqueapproximative.test/admin_dev.php
- **Adminer**: http://adminer.musiqueapproximative.test (root/root)

### Common Commands
```bash
# Clear Symfony cache
docker-compose exec php php symfony cache:clear

# View logs
docker-compose logs -f

# Access PHP container
docker-compose exec php bash

# Stop development
./stop-dev.sh
```

### Deployment
```bash
VERSION=<VERSION>
git hf release start ${VERSION}
git hf release finish ${VERSION}
git checkout ${VERSION}
ant configure build deploy -Dprofile=pastishosting
```

## Recent Work Context

### Recent Conversations (Last 10)
1. **Generate .gitignore File** - Created gitignore for clean version control
2. **Glitch Image in Syndication** - Integrated glitch images into RSS feeds
3. **Volume Slider Visibility** - Fixed audio player volume controls and styling
4. **Styling Page Backgrounds** - Implemented blink/disappear effects, full-width backgrounds
5. **Glitch Logo Background** - Added glitch logo as full-page background on post pages
6. **Logo Glitch Implementation** - Created Symfony task for systematic logo glitching
7. **Glitching Logo with Service** - Integrated gliche.constructions-incongrues.net service
8. **Refining Glitch API** - Enhanced glitch API with scanline jitter, randomize feature, mobile responsiveness
9. **Analyze Project Directory** - Explored musiques-incongrues-tronches project
10. **ADR for LLM IDE Choice** - Documented LLM IDE selection decision

### Current Focus Areas
- Visual effects and glitch aesthetics
- Audio player improvements
- RSS feed enhancements
- Background styling and animations
- Mobile responsiveness

## Key Plugins

### Symfony 1 Plugins
- `sfAdminDashPlugin` - Admin dashboard
- `sfDesastrePlugin` - Disaster effects system
- `sfDoctrineGuardPlugin` - User authentication/authorization
- `sfFeed2Plugin` - RSS/Atom feed generation
- `sfJqueryReloadedPlugin` - jQuery integration
- `sfTaskExtraPlugin` - Additional Symfony tasks

## Important Files

### Configuration
- `src/apps/frontend/config/routing.yml` - URL routing
- `src/apps/frontend/config/desastres.yml` - Disaster recipes
- `src/apps/frontend/config/settings.yml` - App settings
- `src/apps/frontend/config/view.yml` - View configuration

### Templates
- `src/apps/frontend/templates/layout.php` - Main layout template
- `src/apps/frontend/modules/post/templates/showSuccess.php` - Post display template

### Models
- `src/lib/model/doctrine/Post.class.php` - Post model
- `src/lib/model/doctrine/PostTable.class.php` - Post repository

### Assets
- `src/web/theme/musiqueapproximative/` - Theme assets
- `src/web/images/logo_500.png` - Main logo (500x500)
- `src/web/images/glitched_logo.png` - Pre-glitched logo for feeds

## API Endpoints

### Post Endpoints
- `GET /` - Homepage (redirects to latest post)
- `GET /post/:slug` - Display post
- `GET /posts` - List all posts
- `GET /posts?c=:contributor` - Filter by contributor
- `GET /posts?q=:query` - Search posts
- `GET /random` - Random post (JSON)
- `GET /next?current=:id` - Next post (JSON)
- `GET /prev?current=:id` - Previous post (JSON)
- `GET /md5/:md5sum` - Find post by MD5 (JSON)

### Feed Endpoints
- `GET /feed.rss` - RSS feed
- `GET /feed.rss?contributor=:name` - Contributor-specific feed
- `GET /feed.rss?count=:n` - Limit feed items

### Format Endpoints
- `GET /post/:slug.json` - JSON format
- `GET /post/:slug.xspf` - XSPF playlist
- `GET /post/:slug.max` - Max/MSP format

### Embed Endpoints
- `GET /post/:slug?embed=:type` - Embedded player
- `GET /oembed?url=:url&format=:format` - oEmbed endpoint

## Database Schema

### Post Table (Key Fields)
- `id` - Primary key
- `slug` - Unique URL identifier
- `track_author` - Artist name
- `track_title` - Track title
- `track_filename` - Audio file path
- `body` - Description (Markdown)
- `publish_on` - Publication date/time
- `sf_guard_user_id` - Contributor FK
- `created_at` - Creation timestamp
- `updated_at` - Update timestamp

## Design Patterns & Conventions

### Symfony 1 MVC Pattern
- **Models**: Doctrine ORM in `src/lib/model/doctrine/`
- **Views**: PHP templates in `src/apps/frontend/modules/*/templates/`
- **Controllers**: Action classes in `src/apps/frontend/modules/*/actions/`

### Naming Conventions
- Action methods: `execute{ActionName}()`
- Templates: `{actionName}Success.php`
- Routes: `@{module}_{action}`

### Helper Usage
- Load helpers: `$this->getContext()->getConfiguration()->loadHelpers('HelperName')`
- Common helpers: `Markdown`, `Desastre`, `Url`

## Security & Authentication

### sfGuardPlugin
- User management via `sfGuardUser`
- Role-based access control
- Admin interface protection

### CORS & API Access
- JSON API endpoints publicly accessible
- oEmbed support for embedding

## Performance Considerations

### Caching
- Symfony cache in `src/cache/`
- Cache clearing: `php symfony cache:clear`

### Static Assets
- Audio files served directly from `src/web/tracks/`
- Images served from `src/web/images/` and `src/web/theme/`

## Testing & Debugging

### Debug Mode
- Frontend dev: `/frontend_dev.php`
- Admin dev: `/admin_dev.php`
- Web debug toolbar enabled in dev mode

### Logs
- Application logs: `src/log/`
- Docker logs: `docker-compose logs -f`

## External Services

### Gliche Service
- **URL**: https://gliche.constructions-incongrues.net/
- **Purpose**: Image glitching API
- **Parameters**: `seed`, `amount`, `url`
- **Used for**: Logo variations, visual effects

## Theme System

### Current Theme
- `musiqueapproximative` (configurable via `app_theme`)
- Theme assets in `src/web/theme/musiqueapproximative/`

### Theme Assets
- Images: `src/web/theme/{theme}/images/`
- CSS: `src/web/theme/{theme}/css/`
- JavaScript: `src/web/theme/{theme}/js/`

## Audio Player

### JW Player 5.9
- Location: `src/web/swf/mediaplayer-5.9/`
- Format: Flash SWF (legacy)
- Features: Autoplay, custom images, volume control

### Recent Improvements
- Volume slider visibility fixes
- Default volume settings
- CSS styling overrides

## Known Issues & Quirks

### Legacy Technology
- Symfony 1.5 is end-of-life
- Flash player (SWF) deprecated in modern browsers
- PHP 7.4 approaching end-of-life

### File Permissions
- Docker volume mounts may require permission adjustments
- Cache/log directories need write access

### Configuration Files
- Some config files in `.gitignore` (e.g., `app.yml`)
- Environment-specific settings via `.env`

## Future Considerations

### Modernization Opportunities
- Migrate from Symfony 1 to modern framework
- Replace Flash player with HTML5 audio
- Update to PHP 8.x
- Implement modern JavaScript framework
- Improve mobile responsiveness

### Feature Ideas
- Enhanced search capabilities
- User playlists
- Social sharing improvements
- Better mobile audio player
- Progressive Web App (PWA) support

## Useful Resources

### Documentation
- Symfony 1.4 Docs: https://symfony.com/legacy/doc
- Doctrine 1.2 Docs: http://www.doctrine-project.org/projects/orm/1.2/docs/
- XSPF Spec: http://xspf.org/
- JSON API: http://jsonapi.org/
- OpenGraph: http://ogp.me/

### Related Projects
- Gliche Service: https://gliche.constructions-incongrues.net/
- Musiques Incongrues (related): `musiques-incongrues.net/musiques-incongrues-tronches`

---

**Last Updated**: 2026-01-02  
**Maintainer**: Tristan Rivoallan <tristan@rivoallan.net>
