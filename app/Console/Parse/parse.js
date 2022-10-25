import puppeteer from 'puppeteer';

// (async () => {
//     const browser = await puppeteer.launch({
//         headless: true,
//     });
//     const page = await browser.newPage();
//     // await page.goto('https://www.coinbase.com/price/xrp');
//     await page.goto('https://www.coingecko.com/ru');
//     await page.waitForTimeout(1000);
//     let xrp = await page.$eval("tbody", e => e.innerText);
//     // await page.screenshot({path: 'example2.png'});
//     console.log(xrp);
//
//     // Отправить данные по API
//     // await page.goto('http://127.0.0.1:8000/api/v1/xrp');
//
//     // Allows you to intercept a request; must appear before
//     // your first page.goto()
//     await page.setRequestInterception(true);
//
//     // Request intercept handler... will be triggered with
//     // each page.goto() statement
//     page.once('request', interceptedRequest => {
//
//         // Here, is where you change the request method and
//         // add your post data
//         let data = {
//             'method': 'POST',
//             'postData': 'price=' + xrp,
//             headers: {
//                 ...interceptedRequest.headers(),
//                 "Content-Type": "application/x-www-form-urlencoded"
//             }
//         };
//
//         // Request modified... finish sending!
//         interceptedRequest.continue(data);
//     });
//
//     // Navigate, trigger the intercept, and resolve the response
//     const response = await page.goto('http://127.0.0.1:8000/api/v1/xrp');
//     const responseBody = await response.text();
//     console.log(responseBody);
//
//     await browser.close();
// })();

// PROXY TWITTER
(async () => {
    const browser = await puppeteer.launch({
        headless: true,
        executablePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
        args: [
            '--proxy-server=139.59.88.145:8888',
            '--ignore-certificate-errors',
            '--ignore-certificate-errors-spki-list'
        ]
    });

    const page = await browser.newPage();
    await page.setUserAgent(
        "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/95.0.4638.69 Safari/537.36"
    )
    await page.goto('https://twitter.com/elonmusk');
    await page.waitForTimeout(30000);
    await page.screenshot({path: 'twitter.png'});

    // let twitter = await page.$eval("tbody", e => e.innerText);

    await browser.close();
})();
