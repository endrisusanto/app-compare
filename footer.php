<script>
    document.addEventListener('DOMContentLoaded', () => {
        // --- Logika Dark Mode Toggle ---
        const themeToggleBtn = document.getElementById('theme-toggle');
        const darkIcon = document.getElementById('theme-toggle-dark-icon');
        const lightIcon = document.getElementById('theme-toggle-light-icon');
        
        const updateIcons = () => {
            if (document.documentElement.classList.contains('dark')) {
                lightIcon.classList.remove('hidden');
                darkIcon.classList.add('hidden');
            } else {
                darkIcon.classList.remove('hidden');
                lightIcon.classList.add('hidden');
            }
        };

        updateIcons(); // Panggil saat halaman dimuat

        themeToggleBtn.addEventListener('click', () => {
            document.documentElement.classList.toggle('dark');
            const theme = document.documentElement.classList.contains('dark') ? 'dark' : 'light';
            localStorage.setItem('color-theme', theme);
            updateIcons();
        });

        // --- Efek Hover pada Tombol Utama ---
        document.querySelectorAll('.glass-button').forEach(btn => {
            btn.addEventListener('mousemove', e => {
                const rect = btn.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                btn.style.setProperty('--x', `${x}px`);
                btn.style.setProperty('--y', `${y}px`);
            });
        });
    });

    // --- Animasi Latar Belakang Partikel ---
    const canvas=document.getElementById("animated-bg"),ctx=canvas.getContext("2d");canvas.width=window.innerWidth;canvas.height=window.innerHeight;let particles=[],particleCount=75,mouse={x:null,y:null,radius:100};window.addEventListener("mousemove",e=>{mouse.x=e.x,mouse.y=e.y}),window.addEventListener("mouseout",()=>{mouse.x=null,mouse.y=null});class Particle{constructor(){this.x=Math.random()*canvas.width,this.y=Math.random()*canvas.height,this.size=Math.random()*2.5+1,this.baseX=this.x,this.baseY=this.y,this.density=30*Math.random()+1,this.speedX=.4*Math.random()-.2,this.speedY=.4*Math.random()-.2,this.color=document.documentElement.classList.contains("dark")?"rgba(56, 189, 248, 0.7)":"rgba(96, 165, 250, 0.7)"}update(){let e=mouse.x-this.x,t=mouse.y-this.y,o=Math.sqrt(e*e+t*t),s=e/o,a=t/o,i=mouse.radius,n=(i-o)/i,l=s*n*this.density,d=a*n*this.density;o<mouse.radius?(this.x-=l,this.y-=d):(this.x!==this.baseX&&(e=this.x-this.baseX,this.x-=e/10),this.y!==this.baseY&&(t=this.y-this.baseY,this.y-=t/10)),this.x+=this.speedX,this.y+=this.speedY,(this.x>canvas.width||this.x<0)&&(this.speedX*=-1),(this.y>canvas.height||this.y<0)&&(this.speedY*=-1)}draw(){ctx.fillStyle=this.color,ctx.beginPath(),ctx.arc(this.x,this.y,this.size,0,2*Math.PI),ctx.fill()}}function initParticles(){for(let e=0;e<particleCount;e++)particles.push(new Particle)}function animateParticles(){ctx.clearRect(0,0,canvas.width,canvas.height),particles.forEach(e=>{e.update(),e.draw()}),requestAnimationFrame(animateParticles)}initParticles(),animateParticles(),window.addEventListener("resize",()=>{canvas.width=window.innerWidth,canvas.height=window.innerHeight,particles=[],initParticles()});
    </script>
</body>
</html>