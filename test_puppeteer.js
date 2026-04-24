const puppeteer = require('puppeteer');

(async () => {
    const browser = await puppeteer.launch({ headless: "new" });
    const page = await browser.newPage();
    
    page.on('console', msg => {
        console.log(`[BROWSER LOG] ${msg.type()}: ${msg.text()}`);
    });
    
    page.on('pageerror', error => {
        console.log(`[BROWSER ERROR] ${error.message}`);
    });

    try {
        await page.goto('http://localhost:8086/login', {waitUntil: 'networkidle2'});
        await page.type('input[name="nick"]', 'admin');
        await page.type('input[name="password"]', 'admin');
        await page.click('button[type="submit"]');
        await page.waitForNavigation({waitUntil: 'networkidle2'});
        
        await page.goto('http://localhost:8086/index.php?module=Accounting&controller=BankAccounts', {waitUntil: 'networkidle2'});
        await new Promise(r => setTimeout(r, 2000));
    } catch (e) {
        console.log(`[PUPPETEER ERROR] ${e.message}`);
    } finally {
        await browser.close();
    }
})();
