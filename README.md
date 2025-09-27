# AI-Powered Resume Shortlister

An AI-powered resume shortlisting application that helps recruiters streamline the hiring process. This tool analyzes resumes, scores them against job descriptions, and generates summaries to identify the most suitable candidates quickly.

## Features

- **AI-Powered Resume Scoring:** Automatically scores resumes based on their relevance to a specific job description.
- **Resume Summary Generation:** Creates concise summaries of resumes, highlighting key skills and experiences.
- **Job and Resume Management:** Easily upload and manage job descriptions and candidate resumes.
- **Top Candidate Identification:** View a ranked list of candidates based on their scores.

## Installation

1. **Clone the repository:**
   ```bash
   git clone https://github.com/mohd-abdullah/ai-resume-shortlister.git
   cd to project directory
   ```

2. **Install dependencies:**
   ```bash
   composer install
   npm install
   ```

3. **Environment Configuration:**
   - Copy the `.env.example` file to `.env`:
     ```bash
     copy .env.example .env
     ```
   - Generate the application key:
     ```bash
     php artisan key:generate
     ```
   - Configure your database and other environment variables in the `.env` file.

4. **Database Migration:**
   ```bash
   php artisan migrate
   ```

5. **(Optional) Seed the database with dummy data:**
   ```bash
   php artisan db:seed --class=DemoDataSeeder
   ```

6. **Build assets:**
   ```bash
   npm run dev
   ```

7. **Run the development server:**
   ```bash
   php artisan serve
   ```

## Contributing

Contributions are welcome! If you'd like to contribute, please follow these steps:

1. Fork the repository.
2. Create a new branch (`git checkout -b feature/your-feature-name`).
3. Make your changes.
4. Commit your changes (`git commit -m 'Add some feature'`).
5. Push to the branch (`git push origin feature/your-feature-name`).
6. Open a Pull Request.