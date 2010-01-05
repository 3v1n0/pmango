package trie;

public class Block{
	
	// grandezza della pagina
	private int size = 4;
	// array del contenuto delle pagine di memoria
	private String[] parole;
	private int i=0;
	
	public Block(){
		parole = new String[size];
	}
	
	public String[] getWords(){
		return parole;
	}

	//restituisce true se è riuscito ad aggiungere la stringa al blocco, false altrimenti
	public boolean addWord(String word){
		if (i<size) {
			parole[i]=word;
			i++;
			return true;
		}
		else return false;
	}

}
