package trie;

public class Trie {
	
	private Block mem;
	private Trie[] sons = new Trie[2];
	private char read;
	
	public Trie(){
		mem = new Block();
		read = '#';
	}
	
	public Trie(char x){
		mem = new Block();
		read = x;	
	}
	
	// x deve essere una stringa binaria
	public void add(String x){
		if (mem != null){
			if (!mem.addWord(x)){
				sons[0] = new Trie('0');
				sons[1] = new Trie('1');
				for(String w: mem.getWords()){
					if (x.startsWith("0")) {sons[0].add(w.substring(1)/*oppure x*/);}
					else {sons[1].add(w.substring(1)/*oppure x*/);}
				}
				mem = null;
			}
		}
	}
	
	// ricerca di una stringa binaria
	
}
