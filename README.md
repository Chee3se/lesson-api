<file_path>
lesson-scraper/README.md
</file_path>

<edit_description>
Replace the default Laravel README with a project-specific description
</edit_description>

# Lesson Scraper

A web application built with Laravel and React (via Inertia.js) that scrapes and displays lesson timetables from EduPage systems. This tool allows users to view schedules organized by student groups, teachers, and classrooms.

## Features

- **Automatic Scraping**: Fetches timetable data from EduPage servers using custom console commands
- **Multiple Views**: Display schedules by group, teacher, or classroom
- **Week Navigation**: Browse current and future weeks
- **Responsive Design**: Built with Tailwind CSS and React for a modern, mobile-friendly interface
- **Real-time Data**: Keeps schedules up-to-date through periodic scraping

## Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/yourusername/lesson-scraper.git
   cd lesson-scraper
   ```

2. Install PHP dependencies:
   ```bash
   composer install
   ```

3. Install Node.js dependencies:
   ```bash
   npm install
   ```

4. Copy the environment file and configure:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. Run database migrations:
   ```bash
   php artisan migrate
   ```

6. Build assets:
   ```bash
   npm run build
   ```

## Usage

### Scraping Data

Run the scraper command to fetch and update timetable data:

```bash
php artisan scrape:lessons
```

For a full refresh of all data:

```bash
php artisan scrape:all
```

### Running the Application

Start the development server:

```bash
php artisan serve
```

In a separate terminal, start the frontend build process:

```bash
npm run dev
```

Visit `http://localhost:8000` to view the application.

### Available Routes

- `/` - Home page
- `/group/{group}` - View schedule for a specific student group
- `/teacher/{teacher}` - View schedule for a specific teacher
- `/classroom/{classroom}` - View schedule for a specific classroom

## Configuration

The scraper is configured to work with EduPage systems. You may need to adjust the URL and headers in `app/Console/Commands/ScrapeLessons.php` for different institutions.

## Technologies Used

- **Backend**: Laravel 12, PHP 8.2+
- **Frontend**: React 18, Inertia.js, Tailwind CSS
- **Database**: SQLite (configurable)
- **Build Tools**: Vite, TypeScript

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).