<template>
<div class="dcroot">
<div style="width:100%;position:relative;overflow:hidden;background:var(--bg)">
  <AnnouncementBar />
  <div class="blob" style="position:absolute;top:-160px;right:-120px;width:640px;height:640px;border-radius:50%;background:var(--sun)"></div>
  <div class="blob" style="position:absolute;top:260px;left:-220px;width:520px;height:520px;border-radius:50%;background:var(--tint);animation-delay:-5s"></div>

  <div class="wrap" style="position:relative;max-width:1280px;margin:0 auto;padding:0 32px;box-sizing:border-box">

    <SiteHeader class="rise" active="home" search login-button />

    <!-- HERO -->
    <section class="hero" style="display:flex;flex-wrap:wrap;gap:40px;align-items:center;padding:40px 0 72px">
      <div class="hero-text" style="flex:1 1 480px;max-width:600px">
        <span class="rise d1" style="display:inline-block;background:var(--tint);color:var(--green);font-size:13px;font-weight:700;padding:8px 16px;border-radius:999px;letter-spacing:0.3px">Fresh from the Konkan</span>
        <h1 class="rise d2" style="margin:20px 0 0;font-size:clamp(44px,9.5vw,74px);line-height:1.02;font-weight:800;letter-spacing:-2.5px">Taste of the <span class="hl" style="color:var(--green)">Konkan</span></h1>
        <p class="rise d3" style="margin:22px 0 32px;font-size:17px;line-height:1.7;color:var(--muted);max-width:480px">Traditional snacks and herbal powders, made in small batches and delivered to your door across India.</p>
        <div class="rise d4" style="display:flex;gap:14px;flex-wrap:wrap"><a class="btn" href="/products">Shop Now &rarr;</a><a class="btn-line" href="#story">&#9654;&nbsp; Watch our story</a></div>
        <div class="rise d5" style="display:flex;gap:28px;flex-wrap:wrap;margin-top:36px;font-size:13px;font-weight:600;color:var(--muted)"><span>&#10003; Small-batch fresh</span><span>&#10003; Pan-India delivery</span><span>&#10003; Secure payments</span></div>
      </div>

      <!-- hero carousel -->
      <div class="rise d3 hero-car" style="flex:1 1 480px;position:relative;min-height:540px">
        <div class="card hero-card" style="position:absolute;inset:0 0 0 24px;border-radius:60px;overflow:hidden;display:grid">
          <template v-for="(s, s_i) in home.heroSlides" :key="s_i">
            <div :style="`grid-area:1/1;display:flex;align-items:center;justify-content:center;background:${s.bg};opacity:${s.op};transition:opacity .8s ease;pointer-events:${s.pe};position:relative`">
              <div class="hero-circle" :style="`position:absolute;width:380px;height:380px;border-radius:50%;background:#fff;opacity:0.7;transform:scale(${s.scale});transition:transform 1.2s cubic-bezier(.2,.7,.2,1)`"></div>
              <img class="floaty hero-img" :src="s.img" :alt="`${s.name} pack`" :style="`height:410px;width:auto;object-fit:contain;position:relative;transform:translateY(${s.shift}px);transition:transform 1s`">
            </div>
          </template>
        </div>
        <!-- rotating badge -->
        <div class="hero-badge" style="position:absolute;top:-18px;left:0;width:116px;height:116px">
          <svg class="spin" width="116" height="116" viewBox="0 0 116 116" aria-hidden="true"><circle cx="58" cy="58" r="56" fill="#FFB400"/><defs><path id="ring" d="M58,58 m-40,0 a40,40 0 1,1 80,0 a40,40 0 1,1 -80,0"/></defs><text font-size="11.5" font-weight="800" fill="#14301C" letter-spacing="2.4" font-family="Plus Jakarta Sans, sans-serif"><textPath href="#ring">SMALL BATCH • FRESH • KONKAN • </textPath></text></svg>
          <img src="/images/logo.png" alt="" style="position:absolute;left:38px;top:34px;height:48px;width:auto">
        </div>
        <!-- trending chip -->
        <div class="card floaty2 hero-trend" style="position:absolute;top:44px;right:-10px;border-radius:24px;padding:14px 18px;display:flex;align-items:center;gap:14px">
          <div style="width:46px;height:46px;border-radius:14px;background:var(--sun);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;color:var(--amber)">HOT</div>
          <div><div style="font-size:12px;color:var(--muted)">Trending now</div><div style="font-size:15px;font-weight:800">{{ home.heroName }}</div><div style="font-size:14px;font-weight:700;color:var(--green)">{{ home.heroPrice }}</div></div>
        </div>
        <!-- review chip -->
        <div class="card floaty hero-review" style="position:absolute;bottom:84px;left:-16px;border-radius:24px;padding:16px 20px;width:250px;box-sizing:border-box">
          <div style="display:flex;align-items:center;gap:12px"><span style="width:40px;height:40px;border-radius:50%;background:var(--tint);color:var(--green);display:inline-flex;align-items:center;justify-content:center;font-weight:700">P</span><div><div style="font-size:14px;font-weight:700">Priya Kulkarni</div><div style="font-size:12px;color:var(--amber);letter-spacing:2px">&#9733;&#9733;&#9733;&#9733;&#9733;</div></div></div>
          <p style="margin:10px 0 0;font-size:12px;line-height:1.5;color:var(--muted)">Fresh, flavourful and packed so well. Tastes just like homemade.</p>
        </div>
        <!-- controls -->
        <div class="hero-ctrl" style="position:absolute;bottom:20px;left:50px;right:20px;display:flex;align-items:center;justify-content:space-between">
          <div style="display:flex;gap:8px;align-items:center">
            <template v-for="(d, d_i) in home.heroDots" :key="d_i"><button :aria-label="`Show product ${d.n}`" @click="d.go" :style="`height:8px;width:${d.w};background:${d.bg};border:none;border-radius:4px;padding:0;transition:all .4s`"></button></template>
          </div>
          <div style="display:flex;gap:10px"><button class="icon" aria-label="Previous product" @click="home.heroPrev">&lsaquo;</button><button class="icon" aria-label="Next product" @click="home.heroNext" style="background:var(--mango);border-color:var(--mango)">&rsaquo;</button></div>
        </div>
      </div>
    </section>
  </div>

  <!-- TICKER -->
  <div class="marqwrap ticker" style="background:var(--green);color:#fff;overflow:hidden;padding:18px 0;transform:rotate(-1.2deg);margin:0 -20px 24px">
    <div class="marq">
      <template v-for="(t, t_i) in home.ticker" :key="t_i"><span style="display:inline-flex;align-items:center;gap:28px;padding-right:28px;font-size:18px;font-weight:700;white-space:nowrap">{{ t.text }} <span style="color:var(--mango)">&#10022;</span></span></template>
    </div>
  </div>

  <div class="wrap" style="position:relative;max-width:1280px;margin:0 auto;padding:0 32px;box-sizing:border-box">

    <!-- STATS -->
    <section class="stats" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:20px;padding:56px 0 24px">
      <template v-for="(s, s_i) in home.stats" :key="s_i">
        <div class="card lift reveal stat" style="border-radius:32px;padding:28px;box-sizing:border-box;text-align:center">
          <div :style="`font-size:clamp(30px,8vw,44px);font-weight:800;letter-spacing:-1.5px;color:${s.color}`">{{ s.value }}</div>
          <div style="font-size:14px;font-weight:600;color:var(--muted);margin-top:4px">{{ s.label }}</div>
        </div>
      </template>
    </section>

    <!-- CATEGORIES -->
    <section class="sec" style="padding-top:56px">
      <div class="reveal" style="text-align:center;margin-bottom:36px"><div style="font-size:13px;font-weight:700;color:var(--green);letter-spacing:2px">SHOP BY CATEGORY</div><h2 style="margin:8px 0 0;font-size:clamp(28px,7.5vw,40px);font-weight:800;letter-spacing:-1px">Find what you love</h2></div>
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:24px">
        <template v-for="(c, c_i) in home.cats" :key="c_i">
          <a :href="`/products?category=${c.slug}`" class="card lift reveal catcard" :style="`display:flex;align-items:center;gap:20px;border-radius:40px;padding:20px 28px 20px 20px;box-sizing:border-box;background:${c.bg};border-color:transparent;color:var(--ink)`">
            <div class="catimg" style="flex:0 0 110px;height:110px;border-radius:28px;background:#fff;display:flex;align-items:center;justify-content:center;overflow:hidden"><img class="zoomimg" :src="c.img" alt="" style="height:100%;width:auto;object-fit:contain"></div>
            <div style="flex:1;min-width:0"><div style="font-size:22px;font-weight:800;letter-spacing:-0.5px">{{ c.name }}</div><div style="font-size:13px;color:var(--muted);margin-top:2px">{{ c.count }}</div></div>
            <span style="width:40px;height:40px;border-radius:50%;background:var(--ink);color:#fff;display:inline-flex;align-items:center;justify-content:center;font-size:18px">&rarr;</span>
          </a>
        </template>
      </div>
    </section>

    <!-- TRENDY -->
    <section class="sec" style="padding-top:72px">
      <div class="reveal" style="text-align:center;margin-bottom:40px"><div style="font-size:13px;font-weight:700;color:var(--green);letter-spacing:2px">FAVOURITES</div><h2 style="margin:8px 0 0;font-size:clamp(28px,7.5vw,40px);font-weight:800;letter-spacing:-1px">Our Trendy Products</h2></div>
      <p v-if="home.loading" style="text-align:center;color:var(--muted)">Loading products...</p>
      <p v-else-if="home.error" role="alert" style="text-align:center;color:var(--muted)">{{ home.error }} <button type="button" class="btn-line" style="margin-left:8px" @click="home.reload">Try again</button></p>
      <template v-for="(t, t_i) in home.trendy" :key="t_i">
        <div class="card lift reveal trendy" :style="`display:flex;flex-wrap:wrap;gap:40px;align-items:center;border-radius:52px;padding:28px 52px 28px 28px;box-sizing:border-box;margin-bottom:28px;flex-direction:${t.dir}`">
          <div class="trendy-img" :style="`flex:0 0 300px;height:270px;border-radius:40px;background:${t.bg};display:flex;align-items:center;justify-content:center;overflow:hidden`"><img class="zoomimg" :src="t.img" :alt="`${t.name} pack`" style="height:100%;width:auto;object-fit:contain"></div>
          <div class="trendy-body" style="flex:1 1 340px">
            <span style="display:inline-block;background:var(--sun);color:var(--amber);font-size:12px;font-weight:800;padding:6px 14px;border-radius:999px">{{ t.tag }}</span>
            <h3 style="margin:12px 0 0;font-size:clamp(24px,6vw,30px);font-weight:800;letter-spacing:-0.5px">{{ t.name }}</h3>
            <p style="margin:12px 0 16px;font-size:15px;line-height:1.7;color:var(--muted);max-width:520px">{{ t.text }}</p>
            <div style="font-size:28px;font-weight:800;color:var(--green);margin-bottom:20px">{{ t.price }}</div>
            <div style="display:flex;gap:12px;align-items:center"><a class="btn" href="/checkout" @click.prevent="home.buyNow(t.slug)">Buy Now</a><button type="button" class="icon" @click.prevent="home.addToCart(t.slug)" :aria-label="`Add ${t.name} to cart`"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8h12l-1 12H7L6 8z"/><path d="M9 8V6a3 3 0 016 0v2"/></svg></button></div>
          </div>
        </div>
      </template>
    </section>

    <!-- TOP SELLING CAROUSEL -->
    <section class="sec" style="padding-top:64px">
      <div class="reveal" style="display:flex;flex-wrap:wrap;align-items:flex-end;justify-content:space-between;gap:16px;margin-bottom:36px">
        <div><div style="font-size:13px;font-weight:700;color:var(--green);letter-spacing:2px">BEST SELLERS</div><h2 style="margin:8px 0 0;font-size:clamp(28px,7.5vw,40px);font-weight:800;letter-spacing:-1px">Our Top Selling</h2></div>
        <div style="display:flex;gap:10px;align-items:center"><a href="/products" style="font-size:14px;font-weight:700;color:var(--green);margin-right:10px">View all &rarr;</a><button class="icon" aria-label="Previous products" @click="home.topPrev" :style="`opacity:${home.topPrevOp}`">&lsaquo;</button><button class="icon" aria-label="Next products" @click="home.topNext" :style="`background:var(--mango);border-color:var(--mango);opacity:${home.topNextOp}`">&rsaquo;</button></div>
      </div>
      <div class="topclip" style="overflow:hidden;margin:0 -12px;padding:12px">
        <div :style="`display:flex;gap:24px;transform:translateX(${home.topShift}px);transition:transform .6s cubic-bezier(.2,.7,.2,1)`">
          <template v-for="(p, p_i) in home.top" :key="p_i">
            <div class="card lift topcard" style="flex:0 0 284px;border-radius:36px;padding:16px;box-sizing:border-box">
              <a :href="`/product/${p.slug}`" :style="`display:flex;justify-content:center;height:240px;border-radius:26px;background:linear-gradient(160deg,#fff,${p.tone});overflow:hidden;position:relative`"><span style="position:absolute;top:12px;left:12px;background:var(--mango);color:var(--ink);font-size:11px;font-weight:800;padding:4px 10px;border-radius:999px;z-index:1">{{ p.badge }}</span><img class="zoomimg" :src="p.img" :alt="`${p.name} pack`" style="height:100%;width:auto;object-fit:contain"></a>
              <h3 style="margin:16px 6px 4px;font-size:18px;font-weight:700">{{ p.name }}</h3>
              <p style="margin:0 6px 14px;font-size:13px;color:var(--muted)">{{ p.text }}</p>
              <div style="display:flex;justify-content:space-between;align-items:center;margin:0 6px 4px"><span style="font-size:20px;font-weight:800;color:var(--green)">{{ p.price }}</span><button type="button" class="icon" @click.prevent="home.addToCart(p.slug)" :aria-label="`Add ${p.name} to cart`" style="background:var(--mango);border-color:var(--mango)"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8h12l-1 12H7L6 8z"/><path d="M9 8V6a3 3 0 016 0v2"/></svg></button></div>
            </div>
          </template>
        </div>
      </div>
    </section>

    <!-- VIDEO STORY -->
    <section id="story" class="sec" style="padding-top:88px">
      <div class="reveal story" style="display:flex;flex-wrap:wrap;gap:48px;align-items:center;background:linear-gradient(135deg,var(--tint),var(--sun));border-radius:64px;padding:56px;box-sizing:border-box">
        <div class="story-text" style="flex:1 1 340px;max-width:440px">
          <span style="display:inline-block;background:#fff;color:var(--green);font-size:13px;font-weight:700;padding:8px 16px;border-radius:999px;letter-spacing:0.5px">OUR STORY</span>
          <h2 style="margin:18px 0 14px;font-size:clamp(28px,7vw,42px);line-height:1.1;font-weight:800;letter-spacing:-1.2px">From the Konkan kitchen to your home</h2>
          <p style="margin:0 0 22px;font-size:16px;line-height:1.8;color:#4b5a4e">Watch how we make small-batch snacks and herbal powders, and why every pack is sealed fresh.</p>
          <div style="display:flex;flex-direction:column;gap:12px;font-size:15px;font-weight:600">
            <span style="display:flex;align-items:center;gap:12px"><span style="width:28px;height:28px;border-radius:50%;background:var(--green);color:#fff;display:inline-flex;align-items:center;justify-content:center;font-size:13px">1</span>Handpicked ingredients</span>
            <span style="display:flex;align-items:center;gap:12px"><span style="width:28px;height:28px;border-radius:50%;background:var(--green);color:#fff;display:inline-flex;align-items:center;justify-content:center;font-size:13px">2</span>Dried and ground in small batches</span>
            <span style="display:flex;align-items:center;gap:12px"><span style="width:28px;height:28px;border-radius:50%;background:var(--green);color:#fff;display:inline-flex;align-items:center;justify-content:center;font-size:13px">3</span>Sealed fresh and shipped to you</span>
          </div>
        </div>

        <div class="story-media" style="flex:1 1 520px;position:relative">
          <div class="card story-video" style="border-radius:44px;overflow:hidden;aspect-ratio:16/9;position:relative;background:#fff">
            <template v-if="home.notPlaying">
              <div style="position:absolute;inset:0">
                <img src="/images/story-poster.png" alt="Kokango story video preview" style="width:100%;height:100%;object-fit:cover">
                <button aria-label="Play story video" @click="home.play" class="play" style="position:absolute;left:50%;top:50%;margin:-44px 0 0 -44px;width:88px;height:88px;border-radius:50%;background:var(--mango);border:none;color:var(--ink);display:flex;align-items:center;justify-content:center"><svg width="30" height="30" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5l12 7-12 7z"/></svg></button>
                <span style="position:absolute;left:20px;bottom:18px;background:rgba(255,255,255,0.92);color:var(--ink);font-size:13px;font-weight:700;padding:8px 14px;border-radius:999px">&#9654; 0:17 &middot; Our story</span>
              </div>
            </template>
            <template v-if="home.playing">
              <video src="/videos/story.mp4" poster="/images/story-poster.png" controls autoplay playsinline style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;background:#fff"></video>
            </template>
          </div>
          <div class="card floaty2 story-chip" style="position:absolute;right:-12px;bottom:-26px;border-radius:22px;padding:14px 18px;display:flex;align-items:center;gap:12px"><img src="/images/logo.png" alt="" style="height:34px;width:auto"><div style="font-size:13px;font-weight:700;line-height:1.3">Fresh from<br>the Konkan</div></div>
        </div>
      </div>
    </section>

    <!-- FESTIVE HAMPER -->
    <section class="sec" style="padding-top:88px">
      <div class="reveal diwali" style="position:relative;overflow:hidden;display:flex;flex-wrap:wrap;gap:40px;align-items:center;justify-content:space-between;background:linear-gradient(120deg,#0B5D1E,#14301C);border-radius:60px;padding:52px 60px;box-sizing:border-box;color:#fff">
        <div class="blob" style="position:absolute;right:-80px;top:-120px;width:380px;height:380px;border-radius:50%;background:rgba(255,180,0,0.22)"></div>
        <div class="diwali-text" style="position:relative;flex:1 1 380px;max-width:520px">
          <span style="display:inline-block;background:var(--mango);color:var(--ink);font-size:12px;font-weight:800;padding:6px 14px;border-radius:999px;letter-spacing:1px">DIWALI SPECIAL</span>
          <h2 style="margin:16px 0 12px;font-size:clamp(28px,7vw,42px);line-height:1.1;font-weight:800;letter-spacing:-1.2px">Gift a taste of the Konkan this festive season</h2>
          <p style="margin:0 0 26px;font-size:16px;line-height:1.7;color:#d7e6d3">Choose any 4 packs and we will wrap them in a gift-ready box for family and friends.</p>
          <div style="display:flex;align-items:center;gap:20px;flex-wrap:wrap"><a class="btn btn-sun" href="/products">Build your hamper</a><span style="font-size:15px;font-weight:600;color:#d7e6d3">Starting from <span style="font-size:26px;font-weight:800;color:var(--mango)">&#8377; 799</span></span></div>
        </div>
        <div class="diwali-art" style="position:relative;flex:0 0 440px;height:300px;display:flex;align-items:center;justify-content:center">
          <div style="position:absolute;inset:20px 0;border-radius:48px;background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.2)"></div>
          <div class="floaty" style="position:relative;background:#fff;border-radius:28px;padding:14px;margin-right:-18px;transform:rotate(-6deg)"><img class="dw-s" src="/images/jackfruit-chips.png" alt="Jackfruit chips pack" style="height:190px;width:auto;display:block"></div>
          <div class="floaty2" style="position:relative;background:#fff;border-radius:28px;padding:14px;z-index:1"><img class="dw-l" src="/images/moringa-powder.png" alt="Moringa powder pack" style="height:230px;width:auto;display:block"></div>
          <div class="floaty" style="position:relative;background:#fff;border-radius:28px;padding:14px;margin-left:-18px;transform:rotate(6deg);animation-delay:-2s"><img class="dw-s" src="/images/jamun-vadi.png" alt="Jamun vadi pack" style="height:190px;width:auto;display:block"></div>
        </div>
      </div>
    </section>

    <!-- REVIEWS MARQUEE -->
    <section class="sec" style="padding-top:88px">
      <div class="reveal" style="text-align:center;margin-bottom:40px"><div style="font-size:13px;font-weight:700;color:var(--green);letter-spacing:2px">LOVED BY CUSTOMERS</div><h2 style="margin:8px 0 0;font-size:clamp(28px,7.5vw,40px);font-weight:800;letter-spacing:-1px">Customer Reviews</h2></div>
    </section>
  </div>

  <div class="marqwrap" style="overflow:hidden;padding:16px 0 28px">
    <div class="marq slow">
      <template v-for="(r, r_i) in home.reviews" :key="r_i">
        <div class="card revcard" style="flex:0 0 380px;border-radius:32px;padding:28px;box-sizing:border-box;margin-right:24px">
          <div style="font-size:15px;color:var(--amber);letter-spacing:3px">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
          <p style="margin:14px 0 20px;font-size:15px;line-height:1.7;color:var(--ink);min-height:76px">{{ r.text }}</p>
          <div style="display:flex;align-items:center;gap:12px"><span style="width:44px;height:44px;border-radius:50%;background:var(--tint);color:var(--green);display:inline-flex;align-items:center;justify-content:center;font-weight:700">{{ r.initial }}</span><div style="font-size:15px;font-weight:700">{{ r.name }}</div></div>
        </div>
      </template>
    </div>
  </div>

  <div class="wrap" style="position:relative;max-width:1280px;margin:0 auto;padding:0 32px;box-sizing:border-box">

    <!-- COLLECTION SLIDER -->
    <section class="sec" style="padding-top:72px">
      <div class="reveal colbox" style="background:var(--sun);border-radius:60px;padding:40px 56px 32px;box-sizing:border-box">
        <div style="display:grid">
          <template v-for="(c, c_i) in home.colSlides" :key="c_i">
            <div :style="`grid-area:1/1;display:flex;flex-wrap:wrap;gap:40px;align-items:center;opacity:${c.op};transition:opacity .7s ease;pointer-events:${c.pe}`">
              <div class="col-img" style="flex:0 0 380px;height:310px;border-radius:44px;background:#fff;display:flex;align-items:center;justify-content:center;overflow:hidden"><img :src="c.i1" alt="" style="height:80%;width:auto;object-fit:contain;margin-right:-14px"><img :src="c.i2" alt="" style="height:96%;width:auto;object-fit:contain;position:relative"><img :src="c.i3" alt="" :style="`height:80%;width:auto;object-fit:contain;margin-left:-14px;display:${c.d3}`"></div>
              <div class="col-body" style="flex:1 1 360px">
                <div style="font-size:13px;font-weight:700;color:var(--amber);letter-spacing:2px">BEST COLLECTION</div>
                <h2 style="margin:10px 0 0;font-size:clamp(26px,6.5vw,36px);line-height:1.15;font-weight:800;letter-spacing:-1px">{{ c.title }}</h2>
                <p style="margin:14px 0 26px;font-size:15px;line-height:1.7;color:#4d4a35;max-width:520px">{{ c.text }}</p>
                <a class="btn" href="/products">Explore</a>
              </div>
            </div>
          </template>
        </div>
        <div style="display:flex;align-items:center;justify-content:space-between;margin-top:24px;flex-wrap:wrap;gap:12px">
          <div style="display:flex;gap:8px"><template v-for="(d, d_i) in home.colDots" :key="d_i"><button :aria-label="`Show collection ${d.n}`" @click="d.go" :style="`height:8px;width:${d.w};background:${d.bg};border:none;border-radius:4px;padding:0;transition:all .4s`"></button></template></div>
          <div style="display:flex;gap:10px"><button class="icon" aria-label="Previous collection" @click="home.colPrev">&lsaquo;</button><button class="icon" aria-label="Next collection" @click="home.colNext">&rsaquo;</button></div>
        </div>
      </div>
    </section>

    <!-- FOOTER -->
    <SiteFooter variant="full" />
  </div>
</div>
<WhatsAppFab />
</div>
</template>

<script setup>
import AnnouncementBar from '@/components/AnnouncementBar.vue'
import SiteHeader from '@/components/SiteHeader.vue'
import SiteFooter from '@/components/SiteFooter.vue'
import WhatsAppFab from '@/components/WhatsAppFab.vue'
import { useCartStore } from '@/stores/cart'

const cart = useCartStore()
import { useHomePage } from '@/composables/useHomePage'

const home = useHomePage()
</script>

<style scoped>
.dcroot{--bg:#F8F7EF;--surface:#fff;--ink:#14301C;--muted:#566659;--line:#E6E2D0;--green:#0B5D1E;--leaf:#9ACD32;--mango:#FFB400;--amber:#9A5B00;--tint:#EAF3DD;--sun:#FFF1CC}
.dcroot{margin:0;background:var(--bg);color:var(--ink);font-family:'Plus Jakarta Sans','Noto Sans Devanagari',system-ui,sans-serif}
a{color:var(--ink);text-decoration:none}a:hover{color:var(--green)}
button{font-family:inherit;cursor:pointer}
.card{background:var(--surface);border:1px solid var(--line);box-shadow:0 10px 30px rgba(20,48,28,0.06)}
.btn{background:var(--green);color:#fff;border:none;border-radius:999px;padding:12px 28px;font-size:15px;font-weight:600;min-height:44px;display:inline-flex;align-items:center;justify-content:center;gap:8px;transition:transform .25s,background .25s,box-shadow .25s}
.btn:hover{background:#08481a;color:#fff;transform:translateY(-2px);box-shadow:0 10px 22px rgba(11,93,30,0.25)}
.btn-sun{background:var(--mango);color:var(--ink)}.btn-sun:hover{background:#ffc22e;color:var(--ink);box-shadow:0 10px 22px rgba(255,180,0,0.35)}
.btn-line{background:transparent;color:var(--ink);border:1.5px solid var(--ink);border-radius:999px;padding:11px 26px;font-size:15px;font-weight:600;min-height:44px;display:inline-flex;align-items:center;justify-content:center;transition:all .25s}
.btn-line:hover{background:var(--ink);color:#fff;transform:translateY(-2px)}
.icon{width:44px;height:44px;display:inline-flex;align-items:center;justify-content:center;background:#fff;color:var(--ink);border:1px solid var(--line);border-radius:50%;padding:0;position:relative;transition:all .25s}
.icon:hover{border-color:var(--green);color:var(--green);transform:translateY(-2px)}

@keyframes rise{from{opacity:0;transform:translateY(32px)}to{opacity:1;transform:none}}
@keyframes floaty{0%,100%{transform:translateY(0)}50%{transform:translateY(-12px)}}
@keyframes floaty2{0%,100%{transform:translateY(0) rotate(0)}50%{transform:translateY(10px) rotate(-1.5deg)}}
@keyframes spin{to{transform:rotate(360deg)}}
@keyframes marquee{to{transform:translateX(-50%)}}
@keyframes pulse{0%{box-shadow:0 0 0 0 rgba(255,180,0,0.55)}100%{box-shadow:0 0 0 30px rgba(255,180,0,0)}}
@keyframes blob{0%,100%{transform:translate(0,0) scale(1)}50%{transform:translate(24px,-18px) scale(1.07)}}
@keyframes underline{from{background-size:0 100%}to{background-size:100% 100%}}
@keyframes pop{0%{transform:scale(0.7);opacity:0}70%{transform:scale(1.06)}100%{transform:scale(1);opacity:1}}
.rise{animation:rise .9s cubic-bezier(.2,.7,.2,1) both}
.d1{animation-delay:.12s}.d2{animation-delay:.24s}.d3{animation-delay:.36s}.d4{animation-delay:.48s}.d5{animation-delay:.6s}
.floaty{animation:floaty 5s ease-in-out infinite}
.floaty2{animation:floaty2 6s ease-in-out infinite}
.blob{animation:blob 12s ease-in-out infinite}
.spin{animation:spin 18s linear infinite}
.hl{background:linear-gradient(transparent 62%,rgba(255,180,0,0.55) 62%) no-repeat;background-size:100% 100%;animation:underline 1.2s .5s cubic-bezier(.2,.7,.2,1) both}
.lift{transition:transform .35s cubic-bezier(.2,.7,.2,1),box-shadow .35s}
.lift:hover{transform:translateY(-8px);box-shadow:0 22px 44px rgba(20,48,28,0.14)}
.zoomimg{transition:transform .5s cubic-bezier(.2,.7,.2,1)}
.lift:hover .zoomimg{transform:scale(1.07) rotate(-1deg)}
.marq{display:flex;width:max-content;animation:marquee 34s linear infinite}
.marq.slow{animation-duration:55s}
.marqwrap:hover .marq{animation-play-state:paused}
.play{animation:pulse 1.8s ease-out infinite}
@supports (animation-timeline: view()){
  .reveal{animation:rise linear both;animation-timeline:view();animation-range:entry 0% entry 40%}
}
@media (prefers-reduced-motion:reduce){*{animation:none!important;transition:none!important}}

/* ---- responsive ---- */
img{max-width:100%}
.hero-text,.hero-car,.story-text,.story-media,.diwali-text,.trendy-body,.col-body{min-width:0}
@media (max-width:900px){
  .hero{gap:32px!important;padding-bottom:56px!important}
  .hero-text{max-width:none!important}
  .sec{padding-top:64px!important}
  .diwali-art{flex:1 1 100%!important}
  .story-text{max-width:none!important}
  .stats{grid-template-columns:repeat(2,1fr)!important}
  .trendy{flex-direction:column!important;align-items:stretch!important;padding:24px!important;gap:24px!important}
  .trendy-img{flex:0 0 auto!important;width:100%}
  .trendy-body{flex:1 1 auto!important}
  .col-img{flex:1 1 100%!important;width:100%;height:280px!important}
  .col-body{flex-basis:100%!important}
}
@media (max-width:600px){
  .wrap{padding-left:16px!important;padding-right:16px!important}
  .hero{padding-top:20px!important;gap:28px!important;padding-bottom:40px!important}
  .hero-text{flex-basis:100%!important}
  .hero-car{flex:1 1 100%!important;min-height:530px!important;margin-top:8px}
  .hero-card{inset:0!important;border-radius:36px!important}
  .hero-card>div{padding:64px 0 180px!important;box-sizing:border-box}
  .hero-circle{width:min(250px,70%)!important;height:auto!important;aspect-ratio:1}
  .hero-img{height:min(250px,100%)!important;max-width:80%}
  .hero-badge{top:-14px!important;left:-4px!important;transform:scale(.8);transform-origin:top left}
  .hero-trend{top:20px!important;right:8px!important;padding:10px 12px!important;gap:10px!important}
  .hero-review{left:8px!important;bottom:68px!important;width:min(230px,calc(100% - 16px))!important;padding:12px 14px!important}
  .hero-ctrl{left:16px!important;right:12px!important;bottom:12px!important}
  .ticker{margin-left:-24px!important;margin-right:-24px!important}
  .sec{padding-top:48px!important}
  .stats{grid-template-columns:repeat(2,1fr)!important;gap:12px!important;padding-top:40px!important}
  .stat{padding:20px 12px!important;border-radius:24px!important}
  .catcard{padding:14px 16px 14px 14px!important;gap:14px!important;border-radius:32px!important}
  .catimg{flex-basis:84px!important;width:84px;height:84px!important;border-radius:22px!important}
  .trendy{padding:16px 16px 24px!important;gap:20px!important;border-radius:36px!important;flex-direction:column!important;align-items:stretch!important}
  .trendy-img{flex:0 0 auto!important;height:230px!important;border-radius:28px!important;width:100%}
  .trendy-body{flex:1 1 auto!important;padding:0 6px}
  .topclip{margin-left:-16px!important;margin-right:-16px!important;padding-left:16px!important;padding-right:16px!important}
  .story{padding:24px 18px 36px!important;border-radius:36px!important;gap:32px!important}
  .story-text{flex-basis:100%!important}
  .story-media{flex:1 1 100%!important;width:100%}
  .story-video{border-radius:24px!important}
  .story-chip{right:6px!important;bottom:-22px!important}
  .play{transform-origin:center;width:68px!important;height:68px!important;margin:-34px 0 0 -34px!important}
  .diwali{padding:32px 20px!important;border-radius:36px!important;gap:28px!important}
  .diwali-text{flex-basis:100%!important;max-width:none!important}
  .diwali-art{flex:1 1 100%!important;width:100%;height:230px!important}
  .dw-s{height:132px!important}
  .dw-l{height:164px!important}
  .revcard{flex-basis:min(380px,82vw)!important;padding:22px!important}
  .colbox{padding:24px 18px 20px!important;border-radius:36px!important}
  .col-img{flex:1 1 100%!important;height:230px!important;border-radius:28px!important;width:100%}
  .col-body{flex-basis:100%!important}
}
@media (max-width:360px){
  .catimg{flex-basis:72px!important;width:72px;height:72px!important}
}
</style>
