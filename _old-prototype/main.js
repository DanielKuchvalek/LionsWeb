document.addEventListener('DOMContentLoaded', () => {
    loadMatches();
  });
  
  async function loadMatches() {
    const container = document.getElementById('matchesContainer');
    let matches = [];
  
    try {
      const res = await fetch('api/matches.php');
      const data = await res.json();
      if (Array.isArray(data) && data.length > 0) {
        matches = data;
      }
    } catch (err) {
      console.warn("API nedostupné, načítám výchozí data:", err);
    }
  
    // Výchozí budoucí zápasy, pokud databáze vrátí 0 řádků nebo selže spojení
    if (matches.length === 0) {
      matches = [
        {
          category: 'DOMÁCÍ UTKÁNÍ ŽEN',
          home_team: 'LIONS TEAM',
          away_team: 'SKUP OLOMOUC',
          match_date: '2026-10-18 18:00:00'
        },
        {
          category: 'DOROSTENCI MLADŠÍ',
          home_team: 'LIONS TEAM',
          away_team: 'HC ZUBŘÍ',
          match_date: '2026-10-24 15:00:00'
        },
        {
          category: 'DOROSTENCI STARŠÍ',
          home_team: 'LIONS TEAM',
          away_team: 'HC ZUBŘÍ',
          match_date: '2026-10-24 17:00:00'
        }
      ];
    }
  
    container.innerHTML = '';
  
    matches.forEach((m) => {
      const matchDate = new Date(m.match_date);
      const formattedDate = matchDate.toLocaleDateString('cs-CZ', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
  
      const card = document.createElement('div');
      card.className = 'match-card';
      card.setAttribute('data-target-time', matchDate.getTime());
  
      card.innerHTML = `
        <span class="category-pill">${m.category}</span>
        <div class="versus">
          <span class="team home">${m.home_team}</span>
          <span class="vs">VS</span>
          <span class="team away">${m.away_team}</span>
        </div>
        <div class="match-time-label">${formattedDate}</div>
        <div class="countdown">
          <div class="time-box"><span class="days">00</span><small>Dní</small></div>
          <div class="time-box"><span class="hours">00</span><small>Hod</small></div>
          <div class="time-box"><span class="minutes">00</span><small>Min</small></div>
          <div class="time-box"><span class="seconds">00</span><small>Sek</small></div>
        </div>
      `;
  
      container.appendChild(card);
    });
  
    startTimer();
  }
  
  function startTimer() {
    const cards = document.querySelectorAll('.match-card');
  
    setInterval(() => {
      const now = new Date().getTime();
  
      cards.forEach(card => {
        const target = parseInt(card.getAttribute('data-target-time'), 10);
        const diff = target - now;
  
        if (diff <= 0) {
          card.querySelector('.countdown').innerHTML = '<span style="color:#94a3b8;font-weight:700;">ZÁPAS SKONČIL / PROBÍHÁ</span>';
          return;
        }
  
        const days = Math.floor(diff / (1000 * 60 * 60 * 24));
        const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((diff % (1000 * 60)) / 1000);
  
        const daysEl = card.querySelector('.days');
        const hoursEl = card.querySelector('.hours');
        const minutesEl = card.querySelector('.minutes');
        const secondsEl = card.querySelector('.seconds');
  
        if (daysEl) daysEl.textContent = String(days).padStart(2, '0');
        if (hoursEl) hoursEl.textContent = String(hours).padStart(2, '0');
        if (minutesEl) minutesEl.textContent = String(minutes).padStart(2, '0');
        if (secondsEl) secondsEl.textContent = String(seconds).padStart(2, '0');
      });
    }, 1000);
  }