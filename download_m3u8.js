
// 注入html
let $section = document.createElement('section')
$section.innerHTML = `<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <meta name="keywords" content="m3u8 downloader tradional chinese for web">
  <meta name="description" content="m3u8 downloader tradional chinese for web, SAOJSM">
  <title>m3u8 downloader tradional chinese</title>
  <style>
  /*全局設置*/
  html, body {
    margin: 0;
    padding: 0;
  }
  body::-webkit-scrollbar { display: none}
  p {
    margin: 0;
  }
  [v-cloak] {
    display: none;
  }
  #m-app {
    height: 100%;
    width: 100%;
    text-align: center;
    padding: 10px 50px 80px;
    box-sizing: border-box;
  }
  .m-p-action {
    margin: 20px auto;
    max-width: 1100px;
    width: 100%;
    font-size: 35px;
    text-align: center;
    font-weight: bold;
  }
  .m-p-other, .m-p-tamper, .m-p-github, .m-p-language {
    position: fixed;
    right: 50px;
    background-color: #eff3f6;
    background-image: linear-gradient(-180deg, #fafbfc, #eff3f6 90%);
    color: #24292e;
    border: 1px solid rgba(27, 31, 35, .2);
    border-radius: 3px;
    cursor: pointer;
    display: inline-block;
    font-size: 14px;
    font-weight: 600;
    line-height: 20px;
    padding: 6px 12px;
    z-index: 99;
  }
  .m-p-help {
    position: fixed;
    right: 50px;
    top: 50px;
    width: 30px;
    height: 30px;
    color: #666666;
    z-index: 2;
    line-height: 30px;
    font-weight: bolder;
    border-radius: 50%;
    border: 1px solid rgba(27, 31, 35, .2);
    cursor: pointer;
    background-color: #eff3f6;
    background-image: linear-gradient(-180deg, #fafbfc, #eff3f6 90%);
  }
  .m-p-github:hover, .m-p-other:hover, .m-p-tamper:hover, .m-p-help:hover, .m-p-language:hover {
    opacity: 0.9;
  }
  .m-p-language {
    bottom: 30px;
  }
  .m-p-other {
    bottom: 70px;
  }
  .m-p-tamper {
    bottom: 110px;
  }
  .m-p-github {
    bottom: 150px;
  }
  /*廣告*/
  .m-p-refer {
    position: absolute;
    left: 50px;
    bottom: 50px;
  }
  .m-p-refer .text {
    position: absolute;
    top: -80px;
    left: -40px;
    animation-name: upAnimation;
    transform-origin: center bottom;
    animation-duration: 2s;
    animation-fill-mode: both;
    animation-iteration-count: infinite;
    animation-delay: .5s;
  }
  .m-p-refer .close {
    display: block;
    position: absolute;
    top: -110px;
    right: -50px;
    padding: 0;
    margin: 0;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    border: none;
    cursor: pointer;
    z-index: 3;
    transition: 0.3s all;
    background-size: 30px 30px;
    background-repeat: no-repeat;
    background-position: center center;
    background-color: rgba(0, 0, 0, 0.5);
  }
  .m-p-refer .close:hover {
    background-color: rgba(0, 0, 0, 0.8);
  }
  .m-p-refer .link {
    border-radius: 4px;
    text-decoration: none;
    background-color: #4E84E6;
    transition: 0.3s all;
  }
  .m-p-refer .link:hover {
    top: -10px;
    color: #333333;
    border: 1px solid transparent;
    background: rgba(0, 0, 0, 0.6);
    box-shadow: 2px 11px 20px 0 rgba(0, 0, 0, 0.6);
  }
  @keyframes upAnimation {
    0% {
      transform: rotate(0deg);
      transition-timing-function: cubic-bezier(0.215, .61, .355, 1)
    }

    10% {
      transform: rotate(-12deg);
      transition-timing-function: cubic-bezier(0.215, .61, .355, 1)
    }

    20% {
      transform: rotate(12deg);
      transition-timing-function: cubic-bezier(0.215, .61, .355, 1)
    }

    28% {
      transform: rotate(-10deg);
      transition-timing-function: cubic-bezier(0.215, .61, .355, 1)
    }

    36% {
      transform: rotate(10deg);
      transition-timing-function: cubic-bezier(0.755, .5, .855, .06)
    }

    42% {
      transform: rotate(-8deg);
      transition-timing-function: cubic-bezier(0.755, .5, .855, .06)
    }

    48% {
      transform: rotate(8deg);
      transition-timing-function: cubic-bezier(0.755, .5, .855, .06)
    }

    52% {
      transform: rotate(-4deg);
      transition-timing-function: cubic-bezier(0.755, .5, .855, .06)
    }

    56% {
      transform: rotate(4deg);
      transition-timing-function: cubic-bezier(0.755, .5, .855, .06)
    }

    60% {
      transform: rotate(0deg);
      transition-timing-function: cubic-bezier(0.755, .5, .855, .06)
    }

    100% {
      transform: rotate(0deg);
      transition-timing-function: cubic-bezier(0.215, .61, .355, 1)
    }
  }
  /*頂部信息錄入*/
  .m-p-temp-url {
    padding-top: 50px;
    padding-bottom: 10px;
    width: 100%;
    color: #999999;
    text-align: left;
    font-style: italic;
    word-break: break-all;
  }
  .m-p-input-container {
    display: flex;
  }
  .m-p-input-container input {
    flex: 1;
    margin-bottom: 30px;
    display: block;
    width: 280px;
    padding: 16px;
    font-size: 24px;
    border-radius: 4px;
    box-shadow: none;
    color: #444444;
    border: 1px solid #cccccc;
  }
  .m-p-input-container .range-input {
    margin-left: 10px;
    flex: 0 0 100px;
    width: 100px;
    box-sizing: border-box;
  }
  .m-p-input-container div {
    position: relative;
    display: inline-block;
    margin-left: 10px;
    height: 60px;
    line-height: 60px;
    font-size: 24px;
    color: white;
    cursor: pointer;
    border-radius: 4px;
    border: 1px solid #eeeeee;
    background-color: #3D8AC7;
    opacity: 1;
    transition: 0.3s all;
  }
  .m-p-input-container div:hover {
    opacity: 0.9;
  }
  .m-p-input-container div {
    width: 200px;
  }
  .m-p-input-container .disable {
    cursor: not-allowed;
    background-color: #dddddd;
  }
  /*下載狀態*/
  .m-p-line {
    margin: 20px 0 50px;
    vertical-align: top;
    width: 100%;
    height: 5px;
    border-bottom: dotted;
  }
  .m-p-tips {
    width: 100%;
    color: #999999;
    text-align: left;
    font-style: italic;
    word-break: break-all;
  }
  .m-p-tips p {
    width: 100px;
    display: inline-block;
  }
  .m-p-segment {
    text-align: left;
  }
  .m-p-segment .item {
    display: inline-block;
    margin: 10px 6px;
    width: 50px;
    height: 40px;
    color: white;
    line-height: 40px;
    text-align: center;
    border-radius: 4px;
    cursor: help;
    border: solid 1px #eeeeee;
    background-color: #dddddd;
  }
  .m-p-segment .finish {
    background-color: #0ACD76;
  }
  .m-p-segment .error {
    cursor: pointer;
    background-color: #DC5350;
  }
  .m-p-cross, .m-p-final {
    display: inline-block;
    width: 100%;
    height: 50px;
    line-height: 50px;
    font-size: 20px;
    color: white;
    cursor: pointer;
    border-radius: 4px;
    border: 1px solid #eeeeee;
    background-color: #3D8AC7;
    opacity: 1;
    transition: 0.3s all;
  }
  .m-p-final {
    margin-top: 10px;
    text-decoration: none;
  }
  .m-p-force, .m-p-retry {
    position: absolute;
    right: 50px;
    display: inline-block;
    padding: 6px 12px;
    font-size: 18px;
    color: white;
    cursor: pointer;
    border-radius: 4px;
    border: 1px solid #eeeeee;
    background-color: #3D8AC7;
    opacity: 1;
    transition: 0.3s all;
  }
  .m-p-retry {
    right: 250px;
  }
  .m-p-force:hover, .m-p-retry:hover {
    opacity: 0.9;
  }

  </style>
</head>

<body>
<div id="loading">頁面載入中，請耐心等待...</div>
<section id="m-app" v-cloak>
  <!--頂部操作提示-->
  <section class="m-p-action g-box">{{tips}}</section>
  <a class="m-p-github" target="_blank" href="https://github.com/SAOJSM/m3u8-downloader-cht">github</a>

  <!--文件載入-->
  <div class="m-p-temp-url">測試連結：https://vod-normal-global-cdn-z01.afreecatv.com/v3/mapped-vod/save01/afreeca/station/2021/0811/16/1628668696129310.php/live/0/index-f3-v1-a1.m3u8</div>
  <section class="m-p-input-container">
    <input type="text" v-model="url" :disabled="downloading" placeholder="請輸入 m3u8 連結">

    <!--範圍查詢-->
    <template v-if="!downloading || rangeDownload.isShowRange">
      <div v-if="!rangeDownload.isShowRange" @click="getM3U8(true)">特定範圍下載</div>
      <template v-else>
        <input class="range-input" type="number" v-model="rangeDownload.startSegment" :disabled="downloading" placeholder="起始片段">
        <input class="range-input" type="number" v-model="rangeDownload.endSegment" :disabled="downloading" placeholder="截止片段">
      </template>
    </template>

    <!--還未開始下載-->
    <template v-if="!downloading">
      <div @click="getM3U8(false)">原格式下載</div>
      <div @click="getMP4">轉碼為MP4下載</div>
    </template>
    <div v-else-if="finishNum === rangeDownload.targetSegment && rangeDownload.targetSegment > 0" class="disable">下載完成</div>
    <div v-else @click="togglePause">{{ isPause ? '恢復下載' : '暫停下載' }}</div>
  </section>

  <div class="m-p-cross" @click="copyCode">當無法下載，資源發生跨域限制時，在影片源頁面打開Console頁，貼上代碼解決，點擊本按鈕複製代碼</div>
  <a class="m-p-final" target="_blank" href="https://segmentfault.com/a/1190000025182822">下載的影片看不了？試試這個終結解決方案「無差別影片提取工具」，有配套「油猴」插件啦！！！</a>

  <template v-if="finishList.length > 0">
    <div class="m-p-line"></div>
    <div class="m-p-retry" v-if="errorNum && downloadIndex >= rangeDownload.targetSegment" @click="retryAll">重新下載錯誤片段</div>
    <div class="m-p-force" v-if="mediaFileList.length" @click="forceDownload">強制下載現有片段</div>
    <div class="m-p-tips">待下載片段總量：{{ rangeDownload.targetSegment }}，已下載：{{ finishNum }}，錯誤：{{ errorNum }}，進度：{{ (finishNum / rangeDownload.targetSegment * 100).toFixed(2) }}%</div>
    <div class="m-p-tips">若某影片片段下載發生錯誤，將標記為紅色，可點擊相應圖標進行重試</div>
    <section class="m-p-segment">
      <div class="item" v-for="(item, index) in finishList" :class="[item.status]" :title="item.title" @click="retry(index)">{{ index + 1 }}</div>
    </section>
  </template>
</section>
  
</body>
`
$section.style.width = '100%'
$section.style.height = '800px'
$section.style.top = '0'
$section.style.left = '0'
$section.style.position = 'relative'
$section.style.zIndex = '9999'
$section.style.backgroundColor = 'white'
document.body.appendChild($section);

// 載入中 ASE 解密
let $ase = document.createElement('script')
$ase.src = 'https://m3u8-downloader-cht.glitch.me/aes-decryptor.js'

// 載入中 mp4 轉碼
let $mp4 = document.createElement('script')
$mp4.src = 'https://m3u8-downloader-cht.glitch.me/mux-mp4.js'

// 載入中 vue
let $vue = document.createElement('script')
$vue.src = 'https://m3u8-downloader-cht.glitch.me/vue.js'

// 監聽 vue 載入中完成，執行業務代碼
$vue.addEventListener('load', () => {
    new Vue({
        el: '#m-app',

        data() {
            return {
                url: '', // 在線連結
                tips: 'm3u8 影片線上下載工具', // 頂部提示
                isPause: false, // 是否暫停下載
                isGetMP4: false, // 是否轉碼為 MP4 下載
                durationSecond: 0, // 影片持續時長
                isShowRefer: true, // 是否顯示推送
                downloading: false, // 是否下載中
                beginTime: '', // 開始下載的時間
                errorNum: 0, // 錯誤數
                finishNum: 0, // 已下載數
                downloadIndex: 0, // 當前下載片段
                finishList: [], // 下載完成項目
                tsUrlList: [], // ts URL數組
                mediaFileList: [], // 下載的媒體數組
                rangeDownload: { // 特定範圍下載
                    isShowRange: false, // 是否顯示範圍下載
                    startSegment: '', // 起始片段
                    endSegment: '', // 截止片段
                    targetSegment: 1, // 待下載片段
                },
                aesConf: { // AES 影片解密配置
                    method: '', // 加密算法
                    uri: '', // key 所在文件路徑
                    iv: '', // 偏移值
                    key: '', // 秘鑰
                    decryptor: null, // 解碼器對象

                    stringToBuffer: function (str) {
                        return new TextEncoder().encode(str)
                    },
                },
            }
        },

        created() {
            this.getSource();
            document.getElementById('loading') && document.getElementById('loading').remove()
            window.addEventListener('keyup', this.onKeyup)
        },

        beforeDestroy() {
            window.removeEventListener('keyup', this.onKeyup)
        },

        methods: {
            // 獲取連結中攜帶的資源連結
            getSource() {
                let { href } = location
                if (href.indexOf('?source=') > -1) {
                    this.url = href.split('?source=')[1]
                }
            },

            // 退出彈窗
            onKeyup(event) {
                if (event.keyCode === 13) { // 鍵入ESC
                    this.getM3U8()
                }
            },

            // ajax 請求
            ajax(options) {
                options = options || {};
                let xhr = new XMLHttpRequest();
                if (options.type === 'file') {
                    xhr.responseType = 'arraybuffer';
                }

                xhr.onreadystatechange = function () {
                    if (xhr.readyState === 4) {
                        let status = xhr.status;
                        if (status >= 200 && status < 300) {
                            options.success && options.success(xhr.response);
                        } else {
                            options.fail && options.fail(status);
                        }
                    }
                };

                xhr.open("GET", options.url, true);
                xhr.send(null);
            },

            // 合成URL
            applyURL(targetURL, baseURL) {
                baseURL = baseURL || location.href
                if (targetURL.indexOf('http') === 0) {
                    return targetURL
                } else if (targetURL[0] === '/') {
                    let domain = baseURL.split('/')
                    return domain[0] + '//' + domain[2] + targetURL
                } else {
                    let domain = baseURL.split('/')
                    domain.pop()
                    return domain.join('/') + '/' + targetURL
                }
            },

            // 解析為 mp4 下載
            getMP4() {
                this.isGetMP4 = true
                this.getM3U8()
            },

            // 獲取在線文件
            getM3U8(onlyGetRange) {
                if (!this.url) {
                    alert('請輸入連結')
                    return
                }
                if (this.url.toLowerCase().indexOf('.m3u8') === -1) {
                    alert('連結有誤，請重新輸入')
                    return
                }
                if (this.downloading) {
                    alert('資源下載中，請稍候')
                    return
                }

                // 在下載頁面才觸發，代碼注入的頁面不需要校驗
                // 當前協議不一致，切換協議
                if (location.href.indexOf('m3u8-downloader-cht.glitch.me') > -1 && this.url.indexOf(location.protocol) === -1) {
                    alert('當前協議不一致，跳轉至正確頁面重新下載')
                    location.href = `${this.url.split(':')[0]}://m3u8-downloader-cht.glitch.me?source=${this.url}`
                    return
                }

                // 在下載頁面才觸發，修改頁面 URL，攜帶下載路徑，避免刷新後丟失
                if (location.href.indexOf('m3u8-downloader-cht.glitch.me') > -1) {
                    window.history.replaceState(null, '', `${location.href.split('?')[0]}?source=${this.url}`)
                }

                this.tips = 'm3u8 文件下載中，請稍候'
                this.beginTime = new Date()
                this.ajax({
                    url: this.url,
                    success: (m3u8Str) => {
                        this.tsUrlList = []
                        this.finishList = []

                        // 提取 ts 影片片段地址
                        m3u8Str.split('\n').forEach((item) => {
                            if (item.toLowerCase().indexOf('.ts') > -1) {
                                this.tsUrlList.push(this.applyURL(item, this.url))
                                this.finishList.push({
                                    title: item,
                                    status: ''
                                })
                            }
                        })

                        // 僅獲取影片片段數
                        if (onlyGetRange) {
                            this.rangeDownload.isShowRange = true
                            this.rangeDownload.endSegment = this.tsUrlList.length
                            this.rangeDownload.targetSegment = this.tsUrlList.length
                            return
                        } else {
                            let startSegment = Math.max(this.rangeDownload.startSegment || 1, 1) // 最小為 1
                            let endSegment = Math.max(this.rangeDownload.endSegment || this.tsUrlList.length, 1)
                            startSegment = Math.min(startSegment, this.tsUrlList.length) // 最大為 this.tsUrlList.length
                            endSegment = Math.min(endSegment, this.tsUrlList.length)
                            this.rangeDownload.startSegment = Math.min(startSegment, endSegment)
                            this.rangeDownload.endSegment = Math.max(startSegment, endSegment)
                            this.rangeDownload.targetSegment = this.rangeDownload.endSegment - this.rangeDownload.startSegment + 1
                            this.downloadIndex = this.rangeDownload.startSegment - 1
                            this.downloading = true
                        }

                        // 獲取需要下載的 MP4 影片長度
                        if (this.isGetMP4) {
                            let infoIndex = 0
                            m3u8Str.split('\n').forEach(item => {
                                if (item.toUpperCase().indexOf('#EXTINF:') > -1) { // 計算影片總時長，設置 mp4 信息時使用
                                    infoIndex++
                                    if (this.rangeDownload.startSegment <= infoIndex && infoIndex <= this.rangeDownload.endSegment) {
                                        this.durationSecond += parseFloat(item.split('#EXTINF:')[1])
                                    }
                                }
                            })
                        }

                        // 檢測影片 AES 加密
                        if (m3u8Str.indexOf('#EXT-X-KEY') > -1) {
                            this.aesConf.method = (m3u8Str.match(/(.*METHOD=([^,\s]+))/) || ['', '', ''])[2]
                            this.aesConf.uri = (m3u8Str.match(/(.*URI="([^"]+))"/) || ['', '', ''])[2]
                            this.aesConf.iv = (m3u8Str.match(/(.*IV=([^,\s]+))/) || ['', '', ''])[2]
                            this.aesConf.iv = this.aesConf.iv ? this.aesConf.stringToBuffer(this.aesConf.iv) : ''
                            this.aesConf.uri = this.applyURL(this.aesConf.uri, this.url)

                            // let params = m3u8Str.match(/#EXT-X-KEY:([^,]*,?METHOD=([^,]+))?([^,]*,?URI="([^,]+)")?([^,]*,?IV=([^,^\n]+))?/)
                            // this.aesConf.method = params[2]
                            // this.aesConf.uri = this.applyURL(params[4], this.url)
                            // this.aesConf.iv = params[6] ? this.aesConf.stringToBuffer(params[6]) : ''
                            this.getAES();
                        } else if (this.tsUrlList.length > 0) { // 如果影片沒加密，則直接下載片段，否則先下載秘鑰
                            this.downloadTS()
                        } else {
                            this.alertError('資源為空，請查看連結是否有效')
                        }
                    },
                    fail: () => {
                        this.alertError('連結不正確，請查看連結是否有效')
                    }
                })
            },

            // 獲取AES配置
            getAES() {
                alert('影片被 AES 加密，點擊確認，進行影片解碼')
                this.ajax({
                    type: 'file',
                    url: this.aesConf.uri,
                    success: (key) => {
                        // console.log('getAES', key)
                        // this.aesConf.key = this.aesConf.stringToBuffer(key)
                        this.aesConf.key = key
                        this.aesConf.decryptor = new AESDecryptor()
                        this.aesConf.decryptor.constructor()
                        this.aesConf.decryptor.expandKey(this.aesConf.key);
                        this.downloadTS()
                    },
                    fail: () => {
                        this.alertError('AES 配置不正確')
                    }
                })
            },

            // ts 片段的 AES 解碼
            aesDecrypt(data, index) {
                let iv = this.aesConf.iv || new Uint8Array([0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, index])
                return this.aesConf.decryptor.decrypt(data, 0, iv.buffer || iv, true)
            },

            // 下載分片
            downloadTS() {
                this.tips = 'ts 影片片段下載中，請稍候'
                let download = () => {
                    let isPause = this.isPause // 使用另一個變量來保持下載前的暫停狀態，避免回調後沒修改
                    let index = this.downloadIndex
                    this.downloadIndex++
                    if (this.finishList[index] && this.finishList[index].status === '') {
                        this.ajax({
                            url: this.tsUrlList[index],
                            type: 'file',
                            success: (file) => {
                                this.dealTS(file, index, () => this.downloadIndex < this.rangeDownload.endSegment && !isPause && download())
                            },
                            fail: () => {
                                this.errorNum++
                                this.finishList[index].status = 'error'
                                if (this.downloadIndex < this.rangeDownload.endSegment) {
                                    !isPause && download()
                                }
                            }
                        })
                    } else if (this.downloadIndex < this.rangeDownload.endSegment) { // 跳過已經成功的片段
                        !isPause && download()
                    }
                }

                // 建立多少個 ajax 線程
                for (let i = 0; i < Math.min(10, this.rangeDownload.targetSegment - this.finishNum); i++) {
                    download(i)
                }
            },

            // 處理 ts 片段，AES 解密、mp4 轉碼
            dealTS(file, index, callback) {
                const data = this.aesConf.uri ? this.aesDecrypt(file, index) : file
                this.conversionMp4(data, index, (afterData) => { // mp4 轉碼
                    this.mediaFileList[index - this.rangeDownload.startSegment + 1] = afterData // 判斷文件是否需要解密
                    this.finishList[index].status = 'finish'
                    this.finishNum++
                    if (this.finishNum === this.rangeDownload.targetSegment) {
                        this.downloadFile(this.mediaFileList, this.formatTime(this.beginTime, 'YYYY_MM_DD hh_mm_ss'))
                    }
                    callback && callback()
                })
            },

            // 轉碼為 mp4
            conversionMp4(data, index, callback) {
                if (this.isGetMP4) {
                    let transmuxer = new muxjs.Transmuxer({
                        keepOriginalTimestamps: true,
                        duration: parseInt(this.durationSecond),
                    });
                    transmuxer.on('data', segment => {
                        if (index === this.rangeDownload.startSegment - 1) {
                            let data = new Uint8Array(segment.initSegment.byteLength + segment.data.byteLength);
                            data.set(segment.initSegment, 0);
                            data.set(segment.data, segment.initSegment.byteLength);
                            callback(data.buffer)
                        } else {
                            callback(segment.data)
                        }
                    })
                    transmuxer.push(new Uint8Array(data));
                    transmuxer.flush();
                } else {
                    callback(data)
                }
            },

            // 暫停與恢復
            togglePause() {
                this.isPause = !this.isPause
                !this.isPause && this.retryAll()
            },

            // 重新下載某個片段
            retry(index) {
                if (this.finishList[index].status === 'error') {
                    this.finishList[index].status = ''
                    this.ajax({
                        url: this.tsUrlList[index],
                        type: 'file',
                        success: (file) => {
                            this.errorNum--
                            this.dealTS(file, index)
                        },
                        fail: () => {
                            this.finishList[index].status = 'error'
                        }
                    })
                }
            },

            // 重新下載所有錯誤片段
            retryAll() {
                this.finishList.forEach((item) => { // 重置所有片段狀態
                    if (item.status === 'error') {
                        item.status = ''
                    }
                })
                this.errorNum = 0
                this.downloadIndex = this.rangeDownload.startSegment - 1
                this.downloadTS()
            },

            // 下載整合後的TS文件
            downloadFile(fileDataList, fileName) {
                this.tips = 'ts 片段整合中，請留意瀏覽器下載'
                let fileBlob = null
                let a = document.createElement('a')
                if (this.isGetMP4) {
                    fileBlob = new Blob(fileDataList, { type: 'video/mp4' }) // 創建一個Blob對象，並設置文件的 MIME 類型
                    a.download = fileName + '.mp4'
                } else {
                    fileBlob = new Blob(fileDataList, { type: 'video/MP2T' }) // 創建一個Blob對象，並設置文件的 MIME 類型
                    a.download = fileName + '.ts'
                }
                a.href = URL.createObjectURL(fileBlob)
                a.style.display = 'none'
                document.body.appendChild(a)
                a.click()
                a.remove()
            },

            // 格式化時間
            formatTime(date, formatStr) {
                const formatType = {
                    Y: date.getFullYear(),
                    M: date.getMonth() + 1,
                    D: date.getDate(),
                    h: date.getHours(),
                    m: date.getMinutes(),
                    s: date.getSeconds(),
                }
                return formatStr.replace(
                    /Y+|M+|D+|h+|m+|s+/g,
                    target => (new Array(target.length).join('0') + formatType[target[0]]).substr(-target.length)
                )
            },

            // 強制下載現有片段
            forceDownload() {
                if (this.mediaFileList.length) {
                    this.downloadFile(this.mediaFileList, this.formatTime(this.beginTime, 'YYYY_MM_DD hh_mm_ss'))
                } else {
                    alert('當前無已下載片段')
                }
            },

            // 發生錯誤，進行提示
            alertError(tips) {
                alert(tips)
                this.downloading = false
                this.tips = 'm3u8 影片線上下載工具';
            },

            // 拷貝本頁面本身，解決跨域問題
            copyCode() {
                if (this.tips !== '代碼下載中，請稍候') {
                    this.tips = '代碼下載中，請稍候';
                    this.ajax({
                        url: './index.html',
                        success: (fileStr) => {
                            let fileList = fileStr.split(`<!--vue 前端框架--\>`);
                            let dom = fileList[0];
                            let script = fileList[1] + fileList[2];
                            script = script.split('');
                            script = script[1] + script[2];

                            if (this.url) {
                                script = script.replace(`url: '', // 在線連結`, `url: '${this.url}',`);
                            }

                            let codeStr = `
          // 注入html
          let $section = document.createElement('section')
          $section.innerHTML = \`${dom}\`
          $section.style.width = '100%'
          $section.style.height = '800px'
          $section.style.top = '0'
          $section.style.left = '0'
          $section.style.position = 'relative'
          $section.style.zIndex = '9999'
          $section.style.backgroundColor = 'white'
          document.body.appendChild($section);

          // 載入中 ASE 解密
          let $ase = document.createElement('script')
          $ase.src = 'https://m3u8-downloader-cht.glitch.me/aes-decryptor.js'

          // 載入中 mp4 轉碼
          let $mp4 = document.createElement('script')
          $mp4.src = 'hhttps://m3u8-downloader-cht.glitch.me/mux-mp4.js'

          // 載入中 vue
          let $vue = document.createElement('script')
          $vue.src = 'https://m3u8-downloader-cht.glitch.me/vue.js'

          // 監聽 vue 載入中完成，執行業務代碼
          $vue.addEventListener('load', () => {${script}})
          document.body.appendChild($vue);
          document.body.appendChild($mp4);
          document.body.appendChild($ase);
          alert('注入成功，請滾動到頁面底部，若白屏則等待資源載入中')
          `;
                            this.copyToClipboard(codeStr);
                            this.tips = '複製成功，打開影片網頁控制台，注入本代碼';
                        },
                        fail: () => {
                            this.alertError('連結不正確，請查看連結是否有效');
                        },
                    })
                }
            },

            // 拷貝剪切板
            copyToClipboard(content) {
                clearTimeout(this.timeouter)

                if (!document.queryCommandSupported('copy')) {
                    return false
                }

                let $input = document.createElement('textarea')
                $input.style.opacity = '0'
                $input.value = content
                document.body.appendChild($input)
                $input.select()

                const result = document.execCommand('copy')
                document.body.removeChild($input)
                $input = null

                return result
            },

        }
    })
})
document.body.appendChild($vue);
document.body.appendChild($mp4);
document.body.appendChild($ase);
alert('注入成功，請滾動到頁面底部，若白屏則等待資源載入中')
