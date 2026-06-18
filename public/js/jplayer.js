const allPlayers = [];

class AudioPlayer {
    constructor(audioElement, tracks, div_jplayer) {
      this.audio = audioElement;
      this.tracks = tracks;
      this.currentIndex = 0;
	  this.div_jplayer = div_jplayer;

	  this.playBtn = this.div_jplayer.querySelector("#play");
	  this.onClickPlay = () => this.loadTrack(this.currentIndex);
	  this.onClickPause = () => this.pause();
	  this.playBtn.addEventListener("click", this.onClickPlay);
	  this.nextBtn = this.div_jplayer.querySelector("#next");
	  this.onClickNext = () => this.next();
	  this.nextBtn.addEventListener("click", this.onClickNext);
	  this.prevBtn = this.div_jplayer.querySelector("#prev");
	  this.onClickPrevious = () => this.previous();
	  this.prevBtn.addEventListener("click", () => this.this.onClickPrevious);
	  
	  allPlayers.push(this);
    }

    loadTrack(index) {
      if (index < 0 || index >= this.tracks.length) return;
      this.currentIndex = index;
      this.audio.src = this.tracks[index].mp3;
	  this.audio.addEventListener("ended", () => this.next());
	  this.play();
    }

    next() {
      const nextIndex = (this.currentIndex + 1) % this.tracks.length;
      this.loadTrack(nextIndex);
    }

    previous() {
      const prevIndex = (this.currentIndex - 1 + this.tracks.length) % this.tracks.length;
      this.loadTrack(prevIndex);
    }
	
    pause() {
      this.audio.pause();
      this.div_jplayer.querySelector("#play").removeAttribute("class") ;
	  this.div_jplayer.querySelector("#play").setAttribute("class", "play-jplayer") ;  
	  this.div_jplayer.querySelector("#play").removeEventListener("click",  this.onClickPause);
	  this.div_jplayer.querySelector("#play").addEventListener("click",  this.onClickPlay);
    }
	
	play() {
      allPlayers.forEach(p => {
        if (p !== this) p.pause();
      });
      this.audio.play();	
      this.div_jplayer.querySelector("#play").removeAttribute("class") ;
	  this.div_jplayer.querySelector("#play").setAttribute("class", "pause-jplayer") ;  
	  this.div_jplayer.querySelector("#play").removeEventListener("click",  this.onClickPlay);
	  this.div_jplayer.querySelector("#play").addEventListener("click",  this.onClickPause);
    }
}



document.addEventListener("DOMContentLoaded", function() {
	const divs_jplayer = document.querySelectorAll("div.jp-jplayer");
	divs_jplayer.forEach(function(div_jplayer) {
		tracks = JSON.parse(div_jplayer.getAttribute('data-pistes')) ;
		audio = div_jplayer.querySelector("#audio");
		new AudioPlayer(audio, tracks, div_jplayer);
	});
});
