# Flux Budget App 💸

Flux is a personal finance website designed to serve as your **Digital Financial Wallet**. It solves the problem of unorganized personal finance tracking by providing a secure platform where you can easily record, manage, and review your income and expenses to build better financial awareness.

---

## 🔗 Live Demo
> **Check out the live application here:** [Click Here!](https://fluxbudgetapp-production.up.railway.app/login)

---

## ✨ Features

### 📊 Comprehensive Dashboard & Analytics
* **Real-time Overview:** Displays your total balance, income, and expenses as they happen.
* **Visual Insights:** Interactive charts and graphs showing monthly income vs. expenses and categorical breakdowns.
* **Spending Trends:** Track month-over-month changes to understand your financial patterns.
* **Exportable Reports:** Download your expense data in CSV format for external use.

### 📝 Smart Transaction Management
* **Manual Logs:** Add, edit, or delete income and expense records with category and type details.
* **Receipt Documentation:** Upload images of receipts for better record-keeping.
* **Advanced Filtering:** Search and filter transactions by date, description, or type.
* **Live Currency Conversion:** Switch between USD and IDR with a single click.

### 🗓️ Budgeting & Automation
* **Recurring Obligations:** Set one-time entries for monthly bills (e.g., electricity, insurance) that the system automatically tracks.
* **Spending Limits:** Set categorical budget limits and monitor if you are "On Track" or overspending.
* **Financial Projections:** Get estimated monthly fixed costs and yearly projections based on your recurring bills.

### 💡 Intelligence & User Experience
* **Personalized Recommendations:** Receive smart suggestions to optimize your budget, such as diversifying income or reducing high fixed costs.
* **Customization:** Support for both Light and Dark modes and language preferences.

## 🛠️ Tech Stack
* **Backend:** [Laravel](https://laravel.com/) (PHP)
* **Frontend:** Blade Templates, Bootstrap, Vanilla CSS JS
* **Database:** MySQL
* **Tools:** Composer, CSV Exporting

## 🚀 Installation & Setup

### Prerequisites
* PHP 8.1+
* Composer
* MySQL Database

1.  **Clone the Repository**
    ```bash
    git clone [https://github.com/AnangAyman/Flux_Budget_App.git](https://github.com/AnangAyman/Flux_Budget_App.git)
    cd Flux_Budget_App
    ```

2.  **Install Dependencies**
    ```bash
    composer install
    ```

3.  **Environment Setup**
    ```bash
    cp .env.example .env
    php artisan key:generate
    ```

4.  **Database Configuration**
    Update the `.env` file with your local database credentials.

5.  **Run Migrations**
    ```bash
    php artisan migrate
    ```

6.  **Launch the App**
    ```bash
    php artisan serve
    ```

---

**Project Link:** [https://github.com/AnangAyman/Flux_Budget_App](https://github.com/AnangAyman/Flux_Budget_App)
