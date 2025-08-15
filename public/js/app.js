/**
 * URL to Markdown Converter - Frontend JavaScript
 */

class UrlToMarkdownConverter {
    constructor() {
        this.form = document.getElementById('convertForm');
        this.urlInput = document.getElementById('url');
        this.convertBtn = document.getElementById('convertBtn');
        this.btnText = document.querySelector('.btn-text');
        this.btnSpinner = document.querySelector('.btn-spinner');
        
        this.progressSection = document.getElementById('progressSection');
        this.progressBar = document.getElementById('progressFill');
        this.progressText = document.getElementById('progressText');
        this.progressLog = document.getElementById('progressLog');
        
        this.resultsSection = document.getElementById('resultsSection');
        this.errorSection = document.getElementById('errorSection');
        this.errorMessage = document.getElementById('errorMessage');
        
        this.markdownContent = document.getElementById('markdownContent');
        this.htmlContent = document.getElementById('htmlContent');
        
        this.downloadBtn = document.getElementById('downloadBtn');
        this.newConversionBtn = document.getElementById('newConversionBtn');
        this.retryBtn = document.getElementById('retryBtn');
        
        this.currentFileId = null;
        this.progressInterval = null;
        
        this.init();
    }
    
    init() {
        this.form.addEventListener('submit', (e) => this.handleSubmit(e));
        this.downloadBtn.addEventListener('click', () => this.handleDownload());
        this.newConversionBtn.addEventListener('click', () => this.resetForm());
        this.retryBtn.addEventListener('click', () => this.resetForm());
        
        // Auto-focus URL input
        this.urlInput.focus();
    }
    
    async handleSubmit(e) {
        e.preventDefault();
        
        const url = this.urlInput.value.trim();
        if (!url) {
            this.showError('Please enter a valid URL');
            return;
        }
        
        this.startConversion(url);
    }
    
    async startConversion(url) {
        this.setLoadingState(true);
        this.hideAllSections();
        this.showProgressSection();
        this.resetProgress();
        
        try {
            // Start the conversion
            const response = await fetch('/convert', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `url=${encodeURIComponent(url)}`
            });
            
            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.error || 'Conversion failed');
            }
            
            const result = await response.json();
            
            if (result.success) {
                this.currentFileId = result.file_id;
                this.showResults(result.markdown, result.html);
            } else {
                throw new Error(result.error || 'Conversion failed');
            }
            
        } catch (error) {
            console.error('Conversion error:', error);
            this.showError(error.message);
        } finally {
            this.setLoadingState(false);
            this.stopProgressTracking();
        }
    }
    
    showProgressSection() {
        this.progressSection.style.display = 'block';
        this.startProgressTracking();
    }
    
    startProgressTracking() {
        this.progressInterval = setInterval(() => {
            this.updateProgress();
        }, 500);
    }
    
    stopProgressTracking() {
        if (this.progressInterval) {
            clearInterval(this.progressInterval);
            this.progressInterval = null;
        }
    }
    
    async updateProgress() {
        try {
            const response = await fetch('/progress');
            const progress = await response.json();
            
            // Update progress bar
            this.progressBar.style.width = `${progress.progress}%`;
            this.progressText.textContent = `${progress.progress}%`;
            
            // Update log
            if (progress.steps && progress.steps.length > 0) {
                this.updateProgressLog(progress.steps);
            }
            
            // Check for completion or error
            if (progress.progress >= 100 || progress.error) {
                this.stopProgressTracking();
                
                if (progress.error) {
                    this.showError(progress.error);
                }
            }
            
        } catch (error) {
            console.error('Progress update error:', error);
        }
    }
    
    updateProgressLog(steps) {
        // Clear existing log items
        this.progressLog.innerHTML = '';
        
        steps.forEach(step => {
            const logItem = document.createElement('li');
            logItem.className = 'log-item';
            logItem.innerHTML = `
                <span>${step.message}</span>
                <span class="log-timestamp">${step.timestamp}</span>
            `;
            this.progressLog.appendChild(logItem);
        });
        
        // Scroll to bottom
        this.progressLog.scrollTop = this.progressLog.scrollHeight;
    }
    
    showResults(markdown, html) {
        this.hideAllSections();
        this.resultsSection.style.display = 'block';
        
        // Display markdown content
        this.markdownContent.textContent = markdown;
        
        // Display HTML preview
        this.htmlContent.innerHTML = html;
        
        // Scroll to results
        this.resultsSection.scrollIntoView({ behavior: 'smooth' });
    }
    
    showError(message) {
        this.hideAllSections();
        this.errorSection.style.display = 'block';
        this.errorMessage.textContent = message;
        
        // Scroll to error
        this.errorSection.scrollIntoView({ behavior: 'smooth' });
    }
    
    handleDownload() {
        if (this.currentFileId) {
            const downloadUrl = `/download?file=${encodeURIComponent(this.currentFileId)}`;
            
            // Create temporary link and trigger download
            const link = document.createElement('a');
            link.href = downloadUrl;
            link.download = `converted_${new Date().toISOString().slice(0, 19).replace(/:/g, '-')}.md`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            // Show success message
            this.showToast('File downloaded successfully!', 'success');
        }
    }
    
    resetForm() {
        this.hideAllSections();
        this.urlInput.value = '';
        this.urlInput.disabled = false;
        this.urlInput.focus();
        this.currentFileId = null;
        this.resetProgress();
        this.setLoadingState(false);
        this.stopProgressTracking();
    }
    
    resetProgress() {
        this.progressBar.style.width = '0%';
        this.progressText.textContent = '0%';
        this.progressLog.innerHTML = '';
    }
    
    hideAllSections() {
        this.progressSection.style.display = 'none';
        this.resultsSection.style.display = 'none';
        this.errorSection.style.display = 'none';
    }
    
    setLoadingState(loading) {
        this.convertBtn.disabled = loading;
        this.urlInput.disabled = loading;
        
        if (loading) {
            this.btnText.style.display = 'none';
            this.btnSpinner.style.display = 'inline-block';
        } else {
            this.btnText.style.display = 'inline-block';
            this.btnSpinner.style.display = 'none';
        }
    }
    
    showToast(message, type = 'info') {
        // Create toast element
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.textContent = message;
        
        // Style the toast
        Object.assign(toast.style, {
            position: 'fixed',
            top: '20px',
            right: '20px',
            padding: '12px 20px',
            borderRadius: '6px',
            color: 'white',
            fontWeight: '600',
            zIndex: '9999',
            opacity: '0',
            transform: 'translateY(-20px)',
            transition: 'all 0.3s ease'
        });
        
        // Set background color based on type
        const colors = {
            success: '#28a745',
            error: '#dc3545',
            info: '#007bff',
            warning: '#ffc107'
        };
        toast.style.backgroundColor = colors[type] || colors.info;
        
        // Add to DOM
        document.body.appendChild(toast);
        
        // Animate in
        setTimeout(() => {
            toast.style.opacity = '1';
            toast.style.transform = 'translateY(0)';
        }, 100);
        
        // Remove after 3 seconds
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(-20px)';
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 300);
        }, 3000);
    }
}

// Initialize the application when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new UrlToMarkdownConverter();
});
