# URL to Markdown Converter

A clean, responsive PHP 8 web application that converts any URL to a Markdown file with live preview functionality.

## Features

- **URL to Markdown Conversion**: Convert any webpage to clean Markdown format
- **Real-time Progress Tracking**: Step-by-step conversion process with progress bar
- **Side-by-side Preview**: View both raw Markdown and rendered HTML output
- **Download Functionality**: Download the converted Markdown as a .md file
- **Responsive Design**: Works seamlessly on desktop and mobile devices
- **Error Handling**: Comprehensive error handling with user-friendly messages
- **MVC Architecture**: Clean separation of concerns following PHP best practices

## Technical Specifications

- **PHP 8+**: Modern PHP with type declarations and latest features
- **MVC Pattern**: Organized code structure with Controllers, Models, and Views
- **cURL/file_get_contents**: Robust URL fetching with fallback mechanisms
- **Real-time Progress**: AJAX-based progress tracking with session storage
- **Responsive CSS**: Mobile-first design with CSS Grid and Flexbox
- **Vanilla JavaScript**: No external dependencies for frontend functionality

## Project Structure

```
url2md/
├── app/
│   ├── controllers/
│   │   ├── homecontroller.php      # Main page controller
│   │   └── convertcontroller.php   # Conversion logic controller
│   ├── models/
│   │   └── urlconverter.php        # Core conversion model
│   └── views/
│       └── home.php                # Main page template
├── config/
│   └── config.php                  # Application configuration
├── public/
│   ├── css/
│   │   └── style.css               # Application styles
│   └── js/
│       └── app.js                  # Frontend JavaScript
├── storage/
│   ├── downloads/                  # Generated Markdown files
│   └── temp/                       # Temporary files
├── tests/
│   └── url-converter.spec.js       # Playwright test suite
├── index.php                       # Application entry point
├── playwright.config.js            # Test configuration
└── package.json                    # Node.js dependencies for testing
```

## Installation & Setup

1. **Clone the repository**:
   ```bash
   git clone <repository-url>
   cd url2md
   ```

2. **Ensure PHP 8+ is installed**:
   ```bash
   php --version
   ```

3. **Start the development server**:
   ```bash
   php -S localhost:8000
   ```

4. **Open in browser**:
   Navigate to `http://localhost:8000`

## Usage

1. **Enter a URL**: Input any valid HTTP/HTTPS URL in the form
2. **Start Conversion**: Click "Convert to Markdown" to begin the process
3. **Monitor Progress**: Watch the real-time progress bar and step-by-step log
4. **View Results**: See both raw Markdown and rendered HTML preview
5. **Download**: Click "Download Markdown File" to save the .md file
6. **New Conversion**: Use "Convert Another URL" to start over

## Testing

The application includes comprehensive Playwright tests covering:

- Main page functionality
- Form validation
- URL conversion process
- Progress tracking
- Error handling
- Responsive design
- Download functionality

### Running Tests

1. **Install test dependencies**:
   ```bash
   npm install
   ```

2. **Install Playwright browsers**:
   ```bash
   npx playwright install
   ```

3. **Run all tests**:
   ```bash
   npm test
   ```

4. **Run tests with visible browser**:
   ```bash
   npm run test:headed
   ```

5. **View test report**:
   ```bash
   npx playwright show-report
   ```

## Conversion Process

The application follows these steps for URL conversion:

1. **URL Validation**: Validates the input URL format and scheme
2. **Content Fetching**: Downloads the webpage content using cURL or file_get_contents
3. **HTML Parsing**: Parses and cleans the HTML using DOMDocument
4. **Markdown Conversion**: Converts HTML elements to Markdown syntax
5. **File Storage**: Saves the Markdown file for download
6. **Preview Generation**: Creates HTML preview for side-by-side display

## Configuration

Key configuration options in `config/config.php`:

- `USER_AGENT`: HTTP user agent string for requests
- `REQUEST_TIMEOUT`: Timeout for URL fetching (default: 30 seconds)
- `STORAGE_PATH`: Directory for file storage
- `PROGRESS_STEPS`: Conversion step definitions

## Browser Support

- Chrome/Chromium (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)

## Development

The application follows PHP 8 best practices:

- **Type Declarations**: Strict typing throughout the codebase
- **Error Handling**: Comprehensive exception handling
- **Security**: Input validation and sanitization
- **Performance**: Efficient DOM parsing and conversion
- **Maintainability**: Clean MVC architecture

## License

This project is open source and available under the MIT License.

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests for new functionality
5. Ensure all tests pass
6. Submit a pull request
