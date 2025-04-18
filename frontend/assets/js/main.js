// Debug flag
const DEBUG = true;

// Debug logging function
function debugLog(message, data = null) {
    if (DEBUG) {
        console.log(`[Debug] ${message}`, data || '');
    }
}

// Show error message function
function showError(message, error = null) {
    const errorDiv = document.getElementById('loginError');
    if (errorDiv) {
        errorDiv.textContent = message;
        errorDiv.classList.remove('hidden');
    }
    if (DEBUG && error) {
        console.error('Error details:', error);
    }
    debugLog('Error message displayed:', message);
}

// Mock authentication function
async function mockLogin(username, password) {
    debugLog('Mock login attempt:', { username, password });
    
    // Simulate API delay
    await new Promise(resolve => setTimeout(resolve, 1000));
    
    // Mock successful login for admin/admin123
    if (username === 'admin' && password === 'admin123') {
        return {
            success: true,
            user: {
                id: 1,
                username: 'admin',
                role: 'admin'
            }
        };
    }
    
    return {
        success: false,
        message: 'Invalid username or password'
    };
}

// Login form handler
if (document.getElementById('loginForm')) {
    debugLog('Login form found, setting up event listener');
    
    document.getElementById('loginForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        debugLog('Login form submitted');
        
        const username = document.getElementById('username').value;
        const password = document.getElementById('password').value;
        const remember = document.getElementById('remember').checked;

        try {
            const response = await mockLogin(username, password);
            debugLog('Login response:', response);

            if (response.success) {
                debugLog('Login successful, storing user data...');
                // Always store user data, regardless of remember checkbox
                localStorage.setItem('user', JSON.stringify(response.user));
                debugLog('User data stored:', response.user);
                window.location.href = 'dashboard.html';
            } else {
                showError(response.message || 'Login failed. Please check your credentials.');
            }
        } catch (error) {
            debugLog('Login error:', error);
            showError('An error occurred during login.', error);
        }
    });
}

// Mock camera data
const mockCameras = [
    {
        id: 1,
        name: 'Front Door',
        status: 'online',
        lastRecording: '2025-04-17 12:00:00'
    },
    {
        id: 2,
        name: 'Back Yard',
        status: 'online',
        lastRecording: '2025-04-17 11:45:00'
    }
];

// Load cameras on cameras page
if (document.querySelector('.camera-feed')) {
    document.addEventListener('DOMContentLoaded', async function() {
        try {
            // Simulate API delay
            await new Promise(resolve => setTimeout(resolve, 1000));
            
            // Display mock camera data
            const cameraContainer = document.querySelector('.camera-feed');
            if (cameraContainer) {
                cameraContainer.innerHTML = mockCameras.map(camera => `
                    <div class="camera-item">
                        <h3>${camera.name}</h3>
                        <p>Status: ${camera.status}</p>
                        <p>Last Recording: ${camera.lastRecording}</p>
                    </div>
                `).join('');
            }
        } catch (error) {
            console.error('Error loading cameras:', error);
        }
    });
}

// Logout functionality
const logoutButton = document.querySelector('button[data-action="logout"]');
if (logoutButton) {
    logoutButton.addEventListener('click', function(e) {
        e.preventDefault();
                localStorage.removeItem('user');
                window.location.href = 'login.html';
    });
}

// Check authentication state on page load
document.addEventListener('DOMContentLoaded', function() {
    debugLog('Page loaded, checking authentication state');
    const currentPath = window.location.pathname;
    debugLog('Current path:', currentPath);
    
    const user = JSON.parse(localStorage.getItem('user'));
    const isLoginPage = currentPath.endsWith('login.html') || currentPath.endsWith('login.html/');
    
    if (user && isLoginPage) {
        debugLog('User is authenticated but on login page, redirecting to dashboard');
        window.location.href = 'dashboard.html';
    } else if (!user && !isLoginPage) {
        debugLog('User is not authenticated and not on login page, redirecting to login');
        window.location.href = 'login.html';
    }
});

        // Theme toggle functionality
        const themeToggle = document.getElementById('themeToggle');
        const sunIcon = document.getElementById('sunIcon');
        const moonIcon = document.getElementById('moonIcon');
        
        // Check for saved user preference or use system preference
        const savedTheme = localStorage.getItem('theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        document.documentElement.classList.add(savedTheme);
        
        themeToggle.addEventListener('click', () => {
            const isDark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
            
            // Add animation class
            themeToggle.classList.add('animate-spin-slow');
            setTimeout(() => {
                themeToggle.classList.remove('animate-spin-slow');
            }, 500);
        });